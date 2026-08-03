<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\OperationalSetting;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $selectedDate;

    public string $scope = 'all';

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
        return Employee::where('is_active', true)->pluck('id')->toArray();
    }

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function render(
        DashboardScheduleQueriesInterface $scheduleQueries,
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $user = $this->getCurrentUser();
        $employee = $this->getEmployee();
        $now = now();
        $today = $now->toDateString();
        $employeeIds = $this->resolveEmployeeIds();

        $displayName = $employee?->full_name ?? $user?->name ?? 'Operador';
        $teamName = $employee?->team?->name;
        $userRole = $user->getRoleNames()->first() ?? 'operator';
        $roleLabel = match ($userRole) {
            'admin' => 'Administrador',
            'director' => 'Director',
            'wfm' => 'Analista WFM',
            'chief' => 'Jefe',
            'coordinator' => 'Coordinador',
            'supervisor' => 'Supervisor',
            'operator' => 'Operador',
            default => $userRole,
        };
        $greeting = match (true) {
            $now->hour < 12 => 'Buenos días', $now->hour < 19 => 'Buenas tardes', default => 'Buenas noches'
        };

        $currentWeek = $scheduleQueries->getCurrentWeek($today);
        $weekRange = $currentWeek
            ? $currentWeek->week_start_date->format('d M').' - '.$currentWeek->week_end_date->format('d M')
            : $now->startOfWeek()->format('d M').' - '.$now->endOfWeek()->format('d M');

        $scheduledToday = $scheduleQueries->getScheduledCount(null, $today, $now->dayOfWeekIso);

        $states = $realtimeRepo->getRealtimeStates(null);
        $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();
        $totalStates = $states->count();
        $connectedPct = $totalStates > 0 ? round(($connected / $totalStates) * 100, 1) : 0;

        $exceptionsToday = $scheduleQueries->getExceptionCount(null, $today);

        $leaveCounts = $scheduleQueries->getLeaveCounts(null, $today);

        $nowIso = $now->toIso8601String();
        $intradayActive = $scheduleQueries->getActiveIntradayCount(null, $nowIso);
        $intradayToday = $scheduleQueries->getTodayIntradayCount(null, $today);

        $coverage = $totalStates > 0 ? round(($connected / $totalStates) * 100, 1) : 0;

        $opSettings = OperationalSetting::pluck('value', 'key')->toArray();
        $coverageGoal = (int) ($opSettings['goal_coverage'] ?? 80);

        $queues = $realtimeRepo->getQueueStats(0);

        $incidentQuery = AttendanceIncident::query();
        $incidents = [
            ['label' => 'Tardanzas', 'value' => (clone $incidentQuery)->whereDate('incident_date', $today)->whereHas('type', fn ($q) => $q->where('code', 'LATE'))->count()],
            ['label' => 'Ausencias', 'value' => (clone $incidentQuery)->whereDate('incident_date', $today)->whereHas('type', fn ($q) => $q->where('code', 'ABSENT'))->count()],
            ['label' => 'Incapacidades', 'value' => $exceptionsToday],
            ['label' => 'Vacaciones', 'value' => $leaveCounts['approved']],
            ['label' => 'Cambios turno', 'value' => $states->where('current_state', 'SWAP')->count()],
        ];

        $events = $scheduleQueries->getUpcomingEvents($employeeIds, $today);

        $pendingSwaps = $scheduleQueries->getPendingSwapCount(null);

        $requests = [
            ['label' => 'Permisos', 'value' => $leaveCounts['pending']],
            ['label' => 'Cambios turno', 'value' => $pendingSwaps],
            ['label' => 'Incidencias', 'value' => (clone $incidentQuery)->whereNull('admin_comment')->count()],
            ['label' => 'Vacaciones', 'value' => $scheduleQueries->getLeaveCounts(null, $today)['pending']],
        ];

        $teams = Team::withCount([
            'employees' => fn ($q) => $q->where('is_active', true),
        ])->get()->map(function ($team) use ($now, $today) {
            $teamIds = [$team->id];
            $teamEmployeeIds = Employee::whereIn('team_id', $teamIds)->pluck('id');
            $total = $teamEmployeeIds->count();
            $assigned = DB::table('weekly_schedule_assignments')
                ->whereIn('employee_id', $teamEmployeeIds)
                ->where('day_of_week', $now->dayOfWeekIso)
                ->join('weekly_schedules', 'weekly_schedule_assignments.weekly_schedule_id', '=', 'weekly_schedules.id')
                ->where('weekly_schedules.week_start_date', '<=', $today)
                ->where('weekly_schedules.week_end_date', '>=', $today)
                ->count();
            $score = $total > 0 ? round(($assigned / max($total, 1)) * 100) : 0;

            return ['name' => $team->name, 'value' => min(100, $score)];
        })->sortByDesc('value')->take(5)->values();

        $alerts = collect();
        $queues->each(function ($q) use ($alerts) {
            if ($q['state'] === 'critical') {
                $alerts->push(['level' => 'critical', 'message' => "Cola {$q['name']} supera SLA ({$q['sla']})."]);
            } elseif ($q['state'] === 'attention') {
                $alerts->push(['level' => 'attention', 'message' => "Cola {$q['name']} en atención ({$q['sla']})."]);
            }
        });
        if ($exceptionsToday > 0) {
            $alerts->push(['level' => 'attention', 'message' => "{$exceptionsToday} empleados con excepción de horario hoy."]);
        }
        if ($coverage < $coverageGoal) {
            $alerts->push(['level' => 'critical', 'message' => "Cobertura por debajo del objetivo ({$coverage}% vs {$coverageGoal}%)."]);
        }
        if ($alerts->isEmpty()) {
            $alerts->push(['level' => 'normal', 'message' => 'Sin conflictos de horarios.']);
        }
        $alerts = $alerts->take(5);

        $dailyCalls = $realtimeRepo->getCallTrends(
            $now->copy()->subDays(6)->toDateString(),
            $today,
        );
        $maxCalls = $dailyCalls->max() ?: 1;
        $callSparkline = $dailyCalls->map(fn ($c) => max(1, round(($c / $maxCalls) * 8)))->values();

        $dailyAbsences = $scheduleQueries->getAbsenceTrends(
            $now->copy()->subDays(6)->toDateString(),
            $today,
        );
        $maxAbsences = $dailyAbsences->max() ?: 1;
        $absenceSparkline = $dailyAbsences->map(fn ($a) => max(1, round(($a / $maxAbsences) * 8)))->values();

        $trends = [
            ['label' => 'Ausentismo', 'data' => $absenceSparkline->toArray()],
            ['label' => 'Llamadas', 'data' => $callSparkline->toArray()],
        ];

        $hasCritical = $alerts->contains('level', 'critical');
        $hasAttention = $alerts->contains('level', 'attention');
        $operationStatus = $hasCritical
            ? ['label' => 'Requiere Atención', 'state' => 'critical']
            : ($hasAttention
                ? ['label' => 'Atención', 'state' => 'attention']
                : ['label' => 'Operación Normal', 'state' => 'normal']);

        $coverageSlots = $scheduleQueries->getCoverageSlots(null, $today);
        $coverageSeries = $coverageSlots->map(function ($slot) use ($connectedPct, $coverageGoal) {
            $assigned = $slot['assigned'];
            $required = max($coverageGoal, $assigned);
            $available = max(70, (int) round($assigned * ($connectedPct / 100)));

            return ['hour' => $slot['hour'], 'required' => $required, 'available' => min(100, $available)];
        });

        $nextRisk = $coverageSeries->sortBy('available')->first();
        $nextRiskTime = $nextRisk ? $nextRisk['hour'].':00' : '--:--';
        $nextRiskCoverage = $nextRisk ? $nextRisk['available'].'%' : '--%';

        $allEmployees = Employee::where('is_active', true)->get();

        $connectedIds = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->pluck('employee_id')->toArray();

        $leaveEmpIds = LeaveRequest::whereDate('start_time', '<=', $today)
            ->whereDate('end_time', '>=', $today)
            ->where('status', 'approved')
            ->pluck('employee_id')
            ->toArray();

        $excusedIds = ScheduleException::whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->whereHas('reason', fn ($q) => $q->where('is_excused', true))
            ->pluck('employee_id')
            ->toArray();

        $vacacionesReasonId = AbsenceReasonCode::where('short_code', 'V.')->value('id');
        $vacacionesIds = ScheduleException::whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->where('absence_reason_code_id', $vacacionesReasonId)
            ->pluck('employee_id')
            ->toArray();

        $unexcusedIds = ScheduleException::whereDate('start_at', '<=', $today)
            ->whereDate('end_at', '>=', $today)
            ->whereHas('reason', fn ($q) => $q->where('is_excused', false))
            ->pluck('employee_id')
            ->toArray();

        $scheduledIds = WeeklyScheduleAssignment::where('day_of_week', $now->dayOfWeekIso)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->pluck('employee_id')
            ->toArray();

        $presentes = 0;
        $permisos = 0;
        $vacaciones = 0;
        $licencias = 0;
        $ausentes = 0;
        $fueraDeTurno = 0;

        foreach ($allEmployees as $emp) {
            if (in_array($emp->id, $connectedIds)) {
                $presentes++;
            } elseif (in_array($emp->id, $leaveEmpIds)) {
                $permisos++;
            } elseif (in_array($emp->id, $vacacionesIds)) {
                $vacaciones++;
            } elseif (in_array($emp->id, $excusedIds)) {
                $licencias++;
            } elseif (in_array($emp->id, $scheduledIds) || in_array($emp->id, $unexcusedIds)) {
                $ausentes++;
            } else {
                $fueraDeTurno++;
            }
        }

        $distribution = [
            ['label' => 'Presentes', 'value' => $presentes],
            ['label' => 'Permisos', 'value' => $permisos],
            ['label' => 'Vacaciones', 'value' => $vacaciones],
            ['label' => 'Licencias', 'value' => $licencias],
            ['label' => 'Ausentes', 'value' => $ausentes],
            ['label' => 'Fuera de turno', 'value' => $fueraDeTurno],
        ];

        $coverageChartOptions = json_encode([
            'chart' => ['type' => 'area', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'fontFamily' => 'inherit'],
            'series' => [
                ['name' => 'Requerido', 'data' => $coverageSeries->pluck('required')->toArray()],
                ['name' => 'Disponible', 'data' => $coverageSeries->pluck('available')->toArray()],
            ],
            'xaxis' => ['categories' => $coverageSeries->pluck('hour')->toArray(), 'labels' => ['style' => ['fontSize' => '10px']]],
            'yaxis' => ['min' => 0, 'labels' => ['formatter' => ['__callback' => 'percent']], 'forceNiceScale' => true],
            'colors' => ['#94a3b8', '#3b82f6'],
            'fill' => ['opacity' => [0.12, 0.15]],
            'stroke' => ['width' => [2, 2], 'dashArray' => [4, 0], 'curve' => 'smooth'],
            'grid' => ['borderColor' => '#e2e8f0', 'strokeDashArray' => 2, 'padding' => ['left' => 10, 'right' => 10]],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => true, 'position' => 'top', 'fontSize' => '11px', 'markers' => ['width' => 8, 'height' => 8]],
        ]);

        $donutColors = ['#22c55e', '#f59e0b', '#3b82f6', '#8b5cf6', '#ef4444', '#94a3b8'];
        $donutChartOptions = json_encode([
            'chart' => ['type' => 'donut', 'toolbar' => ['show' => false], 'fontFamily' => 'inherit'],
            'series' => array_column($distribution, 'value'),
            'labels' => array_column($distribution, 'label'),
            'colors' => $donutColors,
            'plotOptions' => ['pie' => ['donut' => ['size' => '65%']]],
            'dataLabels' => ['enabled' => false],
            'legend' => ['show' => false],
            'stroke' => ['show' => false],
            'tooltip' => ['y' => ['formatter' => ['__callback' => 'agentCount']]],
            'responsive' => [['breakpoint' => 480, 'options' => ['chart' => ['height' => 200]]]],
        ]);

        $sparklineOptions = [];
        foreach ($trends as $trend) {
            $sparklineOptions[$trend['label']] = json_encode([
                'chart' => ['type' => 'line', 'toolbar' => ['show' => false], 'zoom' => ['enabled' => false], 'sparkline' => ['enabled' => true], 'fontFamily' => 'inherit'],
                'series' => [['name' => $trend['label'], 'data' => $trend['data']]],
                'colors' => ['#3b82f6'],
                'stroke' => ['width' => 2, 'curve' => 'smooth'],
                'tooltip' => ['enabled' => false],
            ]);
        }

        return view('operations::livewire.dashboard', [
            'greeting' => $greeting,
            'displayName' => $displayName,
            'roleLabel' => $roleLabel,
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
                ['label' => 'Permisos hoy', 'value' => (string) ($leaveCounts['approved'] + $leaveCounts['pending']), 'hint' => "{$leaveCounts['approved']} aprobados · {$leaveCounts['pending']} pendientes"],
                ['label' => 'Actividades intradía', 'value' => (string) $intradayToday, 'hint' => "{$intradayActive} en ejecución"],
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
            'coverageChartOptions' => $coverageChartOptions,
            'donutChartOptions' => $donutChartOptions,
            'sparklineOptions' => $sparklineOptions,
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
