<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Models\AttendanceIncident;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\AgentRealtimeRepositoryInterface;
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
        $employee = $this->getEmployee();
        $user = $this->getCurrentUser();

        if (! $employee) {
            return [];
        }

        $isWfmOrAdmin = $user->hasAnyRole(['admin', 'wfm', 'director']);
        $isChief = $user->hasRole('chief') || $employee->hasCoordinatorRights();
        $isSupervisor = $employee->is_manager || $user->hasRole(['supervisor', 'coordinator']);

        if ($isWfmOrAdmin) {
            return [];
        }

        if ($isChief) {
            $teamIds = $employee->getManagedTeamIds();
            $subordinateIds = $employee->getAllSubordinateIds();
            $ids = Employee::whereIn('team_id', $teamIds)
                ->orWhereIn('id', $subordinateIds)
                ->orWhere('id', $employee->id)
                ->pluck('id')
                ->toArray();

            return array_unique($ids);
        }

        if ($isSupervisor) {
            $subordinateIds = $employee->getAllSubordinateIds();

            return array_unique([$employee->id, ...$subordinateIds]);
        }

        return [$employee->id];
    }

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function render(
        DashboardScheduleQueriesInterface $scheduleQueries,
        AgentRealtimeRepositoryInterface $realtimeRepo,
    ) {
        $user = $this->getCurrentUser();
        $employee = $this->getEmployee();
        $now = now();
        $today = $now->toDateString();
        $employeeIds = $this->resolveEmployeeIds();
        $scopeAll = empty($employeeIds);

        $displayName = $employee?->full_name ?? $user?->name ?? 'Operador';
        $teamName = $employee?->team?->name;
        $greeting = match (true) {
            $now->hour < 12 => 'Buenos días', $now->hour < 19 => 'Buenas tardes', default => 'Buenas noches'
        };

        $currentWeek = $scheduleQueries->getCurrentWeek($today);
        $weekRange = $currentWeek
            ? $currentWeek->week_start_date->format('d M').' - '.$currentWeek->week_end_date->format('d M')
            : $now->startOfWeek()->format('d M').' - '.$now->endOfWeek()->format('d M');

        $scheduledToday = $scheduleQueries->getScheduledCount($employeeIds, $today, $now->dayOfWeekIso);

        $states = $realtimeRepo->getRealtimeStates($employeeIds);
        $connected = $states->whereNotIn('current_state', ['LOGOUT', 'OFFLINE', 'UNKNOWN'])->count();
        $totalStates = $states->count();
        $connectedPct = $totalStates > 0 ? round(($connected / $totalStates) * 100, 1) : 0;

        $exceptionsToday = $scheduleQueries->getExceptionCount($employeeIds, $today);

        $leaveCounts = $scheduleQueries->getLeaveCounts($employeeIds, $today);

        $nowIso = $now->toIso8601String();
        $intradayActive = $scheduleQueries->getActiveIntradayCount($employeeIds, $nowIso);
        $intradayToday = $scheduleQueries->getTodayIntradayCount($employeeIds, $today);

        $coverage = $totalStates > 0 ? round(($connected / $totalStates) * 100, 1) : 0;
        $coverageGoal = 95;

        $queues = $realtimeRepo->getQueueStats(6);

        $incidentQuery = $scopeAll ? AttendanceIncident::query() : AttendanceIncident::whereIn('employee_id', $employeeIds);
        $incidents = [
            ['label' => 'Tardanzas', 'value' => (clone $incidentQuery)->whereDate('incident_date', $today)->whereHas('type', fn ($q) => $q->where('code', 'LATE'))->count()],
            ['label' => 'Ausencias', 'value' => (clone $incidentQuery)->whereDate('incident_date', $today)->whereHas('type', fn ($q) => $q->where('code', 'ABSENT'))->count()],
            ['label' => 'Incapacidades', 'value' => $exceptionsToday],
            ['label' => 'Vacaciones', 'value' => $leaveCounts['approved']],
            ['label' => 'Cambios turno', 'value' => $states->where('current_state', 'SWAP')->count()],
        ];

        $events = $scheduleQueries->getUpcomingEvents($employeeIds, $today);

        $pendingSwaps = $scheduleQueries->getPendingSwapCount($employeeIds);

        $requests = [
            ['label' => 'Permisos', 'value' => $leaveCounts['pending']],
            ['label' => 'Cambios turno', 'value' => $pendingSwaps],
            ['label' => 'Incidencias', 'value' => (clone $incidentQuery)->whereNull('admin_comment')->count()],
            ['label' => 'Vacaciones', 'value' => $scheduleQueries->getLeaveCounts($employeeIds, $today)['pending']],
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

        $coverageSlots = $scheduleQueries->getCoverageSlots($employeeIds, $today);
        $coverageSeries = $coverageSlots->map(function ($slot) use ($connectedPct) {
            $assigned = $slot['assigned'];
            $required = max(80, $assigned);
            $available = max(70, (int) round($assigned * ($connectedPct / 100)));

            return ['hour' => $slot['hour'], 'required' => $required, 'available' => min(100, $available)];
        });

        $nextRisk = $coverageSeries->sortBy('available')->first();
        $nextRiskTime = $nextRisk ? $nextRisk['hour'].':00' : '--:--';
        $nextRiskCoverage = $nextRisk ? $nextRisk['available'].'%' : '--%';

        $stateDistribution = $realtimeRepo->getStateDistribution($employeeIds);
        $distribution = [
            ['label' => 'Operando', 'value' => $stateDistribution['operating']],
            ['label' => 'Ready', 'value' => $stateDistribution['ready']],
            ['label' => 'Auxiliar', 'value' => $stateDistribution['auxiliar']],
            ['label' => 'Offline', 'value' => $stateDistribution['offline']],
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
