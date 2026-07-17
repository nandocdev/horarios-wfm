<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\OperationsModule\Services\PerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\TemporalAssignment;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Livewire\Component;

class DailyReport extends Component
{
    public string $view = 'operator';

    public string $date;

    public ?string $teamId = null;

    public bool $showDetails = false;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function switchView(string $view): void
    {
        $this->view = $view;
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
        DashboardScheduleQueriesInterface $scheduleQueries,
        PerformanceService $performanceService,
    ) {
        $user = auth()->user();
        $empModel = $user->employee;
        $employee = $empModel ? Employee::with('team', 'position')->find($empModel->id) : null;
        $isPowerUser = $user->hasAnyRole(['admin', 'wfm', 'superuser', 'chief', 'director']);
        $now = Carbon::parse($this->date);
        $today = $now->toDateString();

        if ($this->view === 'operator' && $employee) {
            $data = $this->buildOperatorData(
                $employee, $now, $realtimeRepo, $scheduleQueries
            );
        } elseif ($this->view === 'coordinator') {
            $teamIds = $isPowerUser
                ? ($this->teamId ? [$this->teamId] : Team::pluck('id')->toArray())
                : ($employee?->getManagedTeamIds() ?? []);

            $teamEmployees = Employee::with('team', 'position')
                ->whereIn('team_id', $teamIds)
                ->where('is_active', true)
                ->orderBy('first_name')
                ->get();

            // Incluir empleados temporalmente asignados a los supervisores de estos equipos
            $supervisorIds = Team::whereIn('id', $teamIds)
                ->whereNotNull('supervisor_id')
                ->pluck('supervisor_id')
                ->toArray();

            $tempEmployeeIds = [];
            foreach ($supervisorIds as $supId) {
                $tempEmployeeIds = array_merge(
                    $tempEmployeeIds,
                    TemporalAssignment::subordinateIdsFor($supId, $now)
                );
            }

            $tempEmployees = ! empty($tempEmployeeIds)
                ? Employee::with('team', 'position')->whereIn('id', array_unique($tempEmployeeIds))->get()
                : collect();

            $employees = $teamEmployees->concat($tempEmployees)->unique('id')->values();

            $data = $employees->map(fn ($e) => $this->buildOperatorData(
                $e, $now, $realtimeRepo, $scheduleQueries
            ))->filter()->values();
        } else {
            $data = collect();
        }

        return view('operations::livewire.daily-report', [
            'reportData' => $data,
            'employee' => $employee,
            'teams' => $isPowerUser ? Team::orderBy('name')->get() : collect(),
        ])->layout('layouts.app', ['title' => 'Reporte Diario']);
    }

    private function buildOperatorData(
        Employee $employee,
        Carbon $now,
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
        DashboardScheduleQueriesInterface $scheduleQueries,
    ): ?array {
        $today = $now->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        // 1. Schedule (entrada, almuerzo, descanso programados)
        $assignments = WeeklyScheduleAssignment::with('schedule')
            ->where('employee_id', $employee->id)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->get();

        $assignment = $assignments->first();
        $schedule = $assignment?->schedule;

        $scheduledEntry = $assignment?->start_time ? Carbon::parse($assignment->start_time)->format('H:i') : '--:--';
        $scheduledLunchStart = $assignment?->lunch_start_time ? Carbon::parse($assignment->lunch_start_time)->format('H:i') : null;
        $scheduledLunchEnd = $assignment?->lunch_end_time ? Carbon::parse($assignment->lunch_end_time)->format('H:i') : null;
        $scheduledBreakStart = $assignment?->break_start_time ? Carbon::parse($assignment->break_start_time)->format('H:i') : null;
        $scheduledBreakEnd = $assignment?->break_end_time ? Carbon::parse($assignment->break_end_time)->format('H:i') : null;
        $lunchDuration = $schedule?->lunch_minutes ?? 0;
        $breakDuration = $schedule?->break_minutes ?? 0;

        // 2. Real-time state
        $states = $realtimeRepo->getRealtimeStates([$employee->id]);
        $state = $states->first();
        $realtimeEntry = $state?->current_state !== 'LOGOUT' && $state?->current_state !== 'OFFLINE'
            ? $state?->last_changed_at ?? null
            : null;

        // 3. Transitions hoy (para calcular tiempo acumulado por estado)
        $transitions = $realtimeRepo->getBatchStateTransitions([$employee->id], $today);
        $timeByState = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
            ->map(fn ($group) => $group->sum('duration'));

        $loggedInSeconds = $timeByState->sum();

        // 4. Call records
        $callStats = $realtimeRepo->getCallStatsForDate($today);

        // 5. Queue performance
        $queuePerformance = $realtimeRepo->getQueuePerformanceReport($today);

        // 6. Intraday activities
        $intradayActivities = $scheduleQueries->getUpcomingEvents([$employee->id], $today, 20);

        // 7. Schedule exceptions
        $exceptionsCount = $scheduleQueries->getExceptionCount([$employee->id], $today);

        return [
            'employee_id' => $employee->id,
            'full_name' => $employee->full_name,
            'team_name' => $employee->team?->name,
            'position_name' => $employee->position?->name,
            'username' => $employee->username,

            // Entrada
            'scheduled_entry' => $scheduledEntry,
            'realtime_entry' => $realtimeEntry ? Carbon::parse($realtimeEntry)->format('H:i') : null,

            // Almuerzo
            'scheduled_lunch' => $scheduledLunchStart && $scheduledLunchEnd
                ? "$scheduledLunchStart - $scheduledLunchEnd"
                : null,
            'lunch_duration' => $lunchDuration,
            'realtime_lunch_seconds' => $timeByState->get('NOT_READY_LUNCH', 0)
                + $timeByState->get('NOT_READY_ALMUERZO', 0)
                + $timeByState->get('LUNCH', 0),

            // Descanso
            'scheduled_break' => $scheduledBreakStart && $scheduledBreakEnd
                ? "$scheduledBreakStart - $scheduledBreakEnd"
                : null,
            'break_duration' => $breakDuration,
            'realtime_break_seconds' => $timeByState->get('NOT_READY_BREAK', 0)
                + $timeByState->get('NOT_READY_DESCANSO', 0)
                + $timeByState->get('BREAK', 0),

            // Estados
            'current_state' => $state?->current_state ?? 'OFFLINE',
            'reason_code' => $state?->reason_code,
            'logged_in_seconds' => $loggedInSeconds,
            'talk_seconds' => $timeByState->get('TALKING', 0),
            'ready_seconds' => $timeByState->get('READY', 0),
            'not_ready_seconds' => $timeByState->get('NOT_READY', 0),
            'work_seconds' => $timeByState->get('WORK', 0),

            // Llamadas
            'calls_total' => (int) ($callStats->total ?? 0),
            'calls_handled' => (int) ($callStats->handled ?? 0),

            // Colas
            'queue_performance' => $queuePerformance->toArray(),

            // Actividades intradía
            'intraday_events' => $intradayActivities->toArray(),

            // Excepciones
            'exceptions' => $exceptionsCount > 0,
        ];
    }
}
