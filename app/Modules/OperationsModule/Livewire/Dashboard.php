<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\AgentRealtimeState;
use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $selectedDate;

    public string $scope = 'all'; // own, team, managed, all

    public int $refreshInterval = 60;

    protected function getCurrentUser()
    {
        return auth()->user();
    }

    protected function getEmployee()
    {
        return $this->getCurrentUser()?->employee;
    }

    protected function resolveEmployeeIds(): array
    {
        $employee = $this->getEmployee();
        $user = $this->getCurrentUser();

        if (! $employee) {
            return [];
        }

        // Role-awareness: scope determina el alcance
        $isWfmOrAdmin = $user->hasAnyRole(['admin', 'wfm', 'director']);
        $isChief = $user->hasRole('chief') || $employee->hasCoordinatorRights();
        $isSupervisor = $employee->is_manager || $user->hasRole(['supervisor', 'coordinator']);

        // Director/WFM → toda la operación
        if ($isWfmOrAdmin) {
            return []; // empty = sin filtro = todos los empleados
        }

        // Chief/Jefe → equipos gestionados + subordinados
        if ($isChief) {
            $teamIds = $employee->getManagedTeamIds();
            $subordinateIds = $employee->getAllSubordinateIds();
            $ids = \App\Modules\PersonnelModule\Models\Employee::whereIn('team_id', $teamIds)
                ->orWhereIn('id', $subordinateIds)
                ->orWhere('id', $employee->id)
                ->pluck('id')
                ->toArray();

            return array_unique($ids);
        }

        // Supervisor → subordinados directos + propio
        if ($isSupervisor) {
            $subordinateIds = $employee->getAllSubordinateIds();

            return array_unique([$employee->id, ...$subordinateIds]);
        }

        // Operador → solo su propio ID
        return [$employee->id];
    }

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function render()
    {
        $user = $this->getCurrentUser();
        $employee = $this->getEmployee();
        $now = now();
        $today = $now->toDateString();
        $employeeIds = $this->resolveEmployeeIds();
        $scopeAll = empty($employeeIds); // sin filtro = todos

        // ── Header ──
        $displayName = $employee?->full_name ?? $user?->name ?? 'Operador';
        $teamName = $employee?->team?->name;
        $greeting = match (true) { $now->hour < 12 => 'Buenos días', $now->hour < 19 => 'Buenas tardes', default => 'Buenas noches' };

        // Semana operativa actual
        $currentWeek = WeeklySchedule::where('week_start_date', '<=', $today)
            ->where('week_end_date', '>=', $today)
            ->first();
        $weekRange = $currentWeek
            ? $currentWeek->week_start_date->format('d M').' - '.$currentWeek->week_end_date->format('d M')
            : now()->startOfWeek()->format('d M').' - '.now()->endOfWeek()->format('d M');

        // ── KPIs ──
        $baseQuery = $scopeAll
            ? WeeklyScheduleAssignment::query()
            : WeeklyScheduleAssignment::whereIn('employee_id', $employeeIds);

        $scheduledToday = (clone $baseQuery)
            ->where('day_of_week', $now->dayOfWeekIso)
            ->whereHas('weeklySchedule', fn ($q) => $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today))
            ->count();

        $statesQuery = $scopeAll
            ? AgentRealtimeState::query()
            : AgentRealtimeState::whereIn('employee_id', $employeeIds);

        $connected = (clone $statesQuery)->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();
        $totalStates = (clone $statesQuery)->count();
        $connectedPct = $totalStates > 0 ? round(($connected / $totalStates) * 100, 1) : 0;

        $exceptionsToday = $scopeAll
            ? ScheduleException::whereDate('start_at', '<=', $today)->whereDate('end_at', '>=', $today)->count()
            : ScheduleException::whereIn('employee_id', $employeeIds)->whereDate('start_at', '<=', $today)->whereDate('end_at', '>=', $today)->count();

        $leavesApproved = $scopeAll
            ? LeaveRequest::whereDate('start_time', '<=', $today)->whereDate('end_time', '>=', $today)->where('status', 'approved')->count()
            : LeaveRequest::whereIn('employee_id', $employeeIds)->whereDate('start_time', '<=', $today)->whereDate('end_time', '>=', $today)->where('status', 'approved')->count();

        $leavesPending = $scopeAll
            ? LeaveRequest::where('status', 'pending')->count()
            : LeaveRequest::whereIn('employee_id', $employeeIds)->where('status', 'pending')->count();

        $nowIso = $now->toIso8601String();
        $intradayActive = $scopeAll
            ? IntradayActivity::whereRaw('time_range @> ?::timestamptz', [$nowIso])->count()
            : IntradayActivity::whereIn('employee_id', $employeeIds)->whereRaw('time_range @> ?::timestamptz', [$nowIso])->count();

        $intradayToday = $scopeAll
            ? IntradayActivity::whereDate('created_at', $today)->count()
            : IntradayActivity::whereIn('employee_id', $employeeIds)->whereDate('created_at', $today)->count();

        $coverage = $totalStates > 0 ? round(($connected / $totalStates) * 100, 1) : 0;
        $coverageGoal = 95;

        // ── Colas (siempre global) ──
        $queues = CsqRealtimeStat::orderByDesc('calls_waiting')->take(6)->get()->map(fn ($q) => [
            'name' => $q->csq_name,
            'waiting' => $q->calls_waiting,
            'handled' => $q->calls_handled ?? 0,
            'aht' => $q->avg_handle_time ? gmdate('i:s', (int) $q->avg_handle_time) : '0:00',
            'sla' => $q->service_level ? round($q->service_level, 1).'%' : '—',
            'state' => ($q->service_level ?? 100) < 80 ? 'critical' : (($q->service_level ?? 100) < 90 ? 'attention' : 'normal'),
        ]);

        // ── Incidencias ──
        $incidentQuery = $scopeAll ? AttendanceIncident::query() : AttendanceIncident::whereIn('employee_id', $employeeIds);
        $incidents = [
            ['label' => 'Tardanzas', 'value' => (clone $incidentQuery)->whereDate('incident_date', $today)->whereHas('type', fn ($q) => $q->where('code', 'LATE'))->count()],
            ['label' => 'Ausencias', 'value' => (clone $incidentQuery)->whereDate('incident_date', $today)->whereHas('type', fn ($q) => $q->where('code', 'ABSENT'))->count()],
            ['label' => 'Incapacidades', 'value' => $exceptionsToday],
            ['label' => 'Vacaciones', 'value' => $leavesApproved],
            ['label' => 'Cambios turno', 'value' => (clone $statesQuery)->where('current_state', 'SWAP')->count()],
        ];

        // ── Eventos pr�ximos (intrad�a) ──
        $eventsQuery = $scopeAll
            ? IntradayActivity::with('activityType', 'employee.team')
                ->whereDate('created_at', $today)->orderBy('time_range')
            : IntradayActivity::with('activityType', 'employee.team')
                ->whereIn('employee_id', $employeeIds)->whereDate('created_at', $today)->orderBy('time_range');

        $events = $eventsQuery->take(6)->get()->map(fn ($a) => [
            'time' => $a->getRangeStart()?->format('H:i') ?? '--:--',
            'title' => $a->activityType?->name ?? 'Actividad',
            'detail' => ($a->employee?->team?->name ?? '').($a->employee ? ' · '.$a->employee->full_name : ''),
        ]);

        // ── Solicitudes pendientes ──
        $pendingQuery = $scopeAll ? LeaveRequest::query() : LeaveRequest::whereIn('employee_id', $employeeIds);
        $pendingSwaps = $scopeAll ? ShiftSwapRequest::where('status', 'pending')->count() : ShiftSwapRequest::whereIn('requester_id', $employeeIds)->where('status', 'pending')->count();

        $requests = [
            ['label' => 'Permisos', 'value' => (clone $pendingQuery)->where('status', 'pending')->count()],
            ['label' => 'Cambios turno', 'value' => $pendingSwaps],
            ['label' => 'Incidencias', 'value' => (clone $incidentQuery)->whereNull('admin_comment')->count()],
            ['label' => 'Vacaciones', 'value' => (clone $pendingQuery)->where('status', 'pending')->where('type', 'vacation')->count()],
        ];

        // ── Equipos (score de cumplimiento simulado con datos reales) ──
        $teams = \App\Modules\PersonnelModule\Models\Team::withCount([
            'employees' => fn ($q) => $q->where('is_active', true),
        ])->get()->map(function ($team) use ($now, $today) {
            $teamIds = [$team->id];
            $teamEmployeeIds = \App\Modules\PersonnelModule\Models\Employee::whereIn('team_id', $teamIds)->pluck('id');
            $total = $teamEmployeeIds->count();
            $assigned = WeeklyScheduleAssignment::whereIn('employee_id', $teamEmployeeIds)
                ->where('day_of_week', $now->dayOfWeekIso)
                ->whereHas('weeklySchedule', fn ($q) => $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today))
                ->count();
            $score = $total > 0 ? round(($assigned / max($total, 1)) * 100) : 0;

            return ['name' => $team->name, 'value' => min(100, $score)];
        })->sortByDesc('value')->take(5)->values();

        // ── Alertas din�micas ──
        $alerts = collect();
        $queues->each(function ($q) use ($alerts) {
            if ($q['state'] === 'critical') {
                $alerts->push(['level' => 'critical', 'message' => "Cola {$q['name']} supera SLA ({$q['sla']})."]);
            } elseif ($q['state'] === 'attention') {
                $alerts->push(['level' => 'attention', 'message' => "Cola {$q['name']} en atenci�n ({$q['sla']})."]);
            }
        });
        if ($exceptionsToday > 0) {
            $alerts->push(['level' => 'attention', 'message' => "{$exceptionsToday} empleados con excepci�n de horario hoy."]);
        }
        if ($coverage < $coverageGoal) {
            $alerts->push(['level' => 'critical', 'message' => "Cobertura por debajo del objetivo ({$coverage}% vs {$coverageGoal}%)."]);
        }
        if ($alerts->isEmpty()) {
            $alerts->push(['level' => 'normal', 'message' => 'Sin conflictos de horarios.']);
        }
        $alerts = $alerts->take(5);

        // ── Tendencias semanales (mini sparklines con datos reales de los �ltimos 7 d�as) ──
        $dailyCalls = DB::table('agent_call_performance')
            ->whereDate('start_time', '>=', $now->copy()->subDays(6)->toDateString())
            ->whereDate('start_time', '<=', $today)
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('COUNT(*) as calls'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('calls');
        $maxCalls = $dailyCalls->max() ?: 1;
        $callSparkline = $dailyCalls->map(fn ($c) => max(1, round(($c / $maxCalls) * 8)))->values();

        $dailyAbsences = DB::table('schedule_exceptions')
            ->whereDate('start_at', '>=', $now->copy()->subDays(6)->toDateString())
            ->whereDate('start_at', '<=', $today)
            ->select(DB::raw('DATE(start_at) as date'), DB::raw('COUNT(*) as absences'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('absences');
        $maxAbsences = $dailyAbsences->max() ?: 1;
        $absenceSparkline = $dailyAbsences->map(fn ($a) => max(1, round(($a / $maxAbsences) * 8)))->values();

        $trends = [
            ['label' => 'Ausentismo', 'data' => $absenceSparkline->toArray()],
            ['label' => 'Llamadas', 'data' => $callSparkline->toArray()],
        ];

        // ── Estado de la operaci�n ──
        $hasCritical = $alerts->contains('level', 'critical');
        $hasAttention = $alerts->contains('level', 'attention');
        $operationStatus = $hasCritical
            ? ['label' => 'Requiere Atenci�n', 'state' => 'critical']
            : ($hasAttention
                ? ['label' => 'Atenci�n', 'state' => 'attention']
                : ['label' => 'Operaci�n Normal', 'state' => 'normal']);

        // ── Cobertura del d�a (simulada con datos reales de asignaciones) ──
        $coverageSeries = collect();
        for ($h = 6; $h <= 17; $h++) {
            $hourSlot = $h < 10 ? "0{$h}:00" : "{$h}:00";
            $assignedAtHour = $scopeAll
                ? WeeklyScheduleAssignment::where('start_time', '<=', $hourSlot)
                    ->where('end_time', '>=', $hourSlot)
                    ->whereHas('weeklySchedule', fn ($q) => $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today))
                    ->count()
                : WeeklyScheduleAssignment::whereIn('employee_id', $employeeIds)
                    ->where('start_time', '<=', $hourSlot)
                    ->where('end_time', '>=', $hourSlot)
                    ->whereHas('weeklySchedule', fn ($q) => $q->where('week_start_date', '<=', $today)->where('week_end_date', '>=', $today))
                    ->count();
            $required = max(80, $assignedAtHour);
            $available = max(70, (int) round($assignedAtHour * ($connectedPct / 100)));
            $coverageSeries->push(['hour' => (string) $h, 'required' => $required, 'available' => min(100, $available)]);
        }

        $nextRisk = $coverageSeries->sortBy('available')->first();
        $nextRiskTime = $nextRisk ? $nextRisk['hour'].':00' : '--:--';
        $nextRiskCoverage = $nextRisk ? $nextRisk['available'].'%' : '--%';

        // ── Distribuci�n real desde agent_realtime_states ──
        $stateGroups = (clone $statesQuery)
            ->selectRaw('current_state, COUNT(*) as cnt')
            ->groupBy('current_state')
            ->pluck('cnt', 'current_state');
        $distribution = [
            ['label' => 'Operando', 'value' => (int) ($stateGroups->get('TALKING', 0) + $stateGroups->get('WORK', 0) + $stateGroups->get('RESERVED', 0))],
            ['label' => 'Ready', 'value' => (int) $stateGroups->get('READY', 0)],
            ['label' => 'Auxiliar', 'value' => (int) $stateGroups->get('NOT_READY', 0)],
            ['label' => 'Offline', 'value' => (int) ($stateGroups->get('LOGOUT', 0) + $stateGroups->get('OFFLINE', 0) + $stateGroups->get('UNKNOWN', 0))],
        ];

        return view('operations::livewire.dashboard', [
            'greeting' => $greeting,
            'displayName' => $displayName,
            'todayLabel' => $now->locale('es')->translatedFormat('l d F Y'),
            'currentTime' => $now->format('H:i'),
            'weekRange' => $weekRange,
            'operationStatus' => $operationStatus,
            'shift' => [
                'start' => $employee?->baseSchedule?->start_time ?? '--:--',
                'end' => $employee?->baseSchedule?->end_time ?? '--:--',
                'team' => $teamName ?? '—',
                'supervisor' => $employee?->manager?->full_name ?? '—',
            ],
            'kpis' => [
                ['label' => 'Personal Programado', 'value' => (string) $scheduledToday, 'hint' => ''],
                ['label' => 'Conectados', 'value' => (string) $connected, 'hint' => "{$connectedPct}%"],
                ['label' => 'Ausentes', 'value' => (string) $exceptionsToday, 'hint' => $totalStates > 0 ? round(($exceptionsToday / max($totalStates, 1)) * 100, 1).'%' : '0%'],
                ['label' => 'Permisos hoy', 'value' => (string) ($leavesApproved + $leavesPending), 'hint' => "{$leavesApproved} aprobados · {$leavesPending} pendientes"],
                ['label' => 'Actividades intrad�a', 'value' => (string) $intradayToday, 'hint' => "{$intradayActive} en ejecuci�n"],
                ['label' => 'Cobertura', 'value' => "{$coverage}%", 'hint' => "Objetivo {$coverageGoal}%"],
            ],
            'coverageSeries' => $coverageSeries,
            'nextRisk' => ['time' => $nextRiskTime, 'coverage' => $nextRiskCoverage],
            'distribution' => $distribution,
            'queues' => $queues,
            'incidents' => $incidents,
            'events' => $events,
            'requests' => $requests,
            'teams' => $teams,
            'alerts' => $alerts,
            'trends' => $trends,
            'quickActions' => [
                ['label' => 'Crear permiso', 'route' => 'schedules.leave-request'],
                ['label' => 'Registrar incidencia', 'route' => 'schedules.exceptions'],
                ['label' => 'Ver horarios', 'route' => 'schedules.my-schedule'],
                ['label' => 'Planificación semanal', 'route' => 'schedules.planning'],
                ['label' => 'Reportes', 'route' => 'operations.reports'],
                ['label' => 'Cobertura', 'route' => 'operations.availability'],
            ],
            'footer' => [
                'connectedUsers' => $connected,
                'lastCalculation' => $now->format('H:i'),
                'lastSchedulesPublished' => $currentWeek?->published_at?->format('d F Y') ?? '—',
                'nextRefresh' => $now->addMinutes(1)->format('H:i'),
            ],
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
