<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\OperationsModule\Services\PerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Livewire\Component;

class MyMetrics extends Component
{
    public string $date;

    public function mount(): void
    {
        $this->date = now()->toDateString();
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
        $employee = $user->employee ? Employee::with('team', 'position')->find($user->employee->id) : null;
        $now = Carbon::parse($this->date);
        $today = $now->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        $metrics = [];
        $schedule = null;
        $states = [];
        $callStats = null;
        $queuePerf = [];
        $intradayEvents = [];
        $shrinkage = 0;
        $heroKpis = [];

        if ($employee) {
            // 1. Schedule
            $assignments = WeeklyScheduleAssignment::with('schedule')
                ->where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->whereHas('weeklySchedule', fn ($q) => $q
                    ->where('week_start_date', '<=', $today)
                    ->where('week_end_date', '>=', $today)
                )
                ->get();

            $schedule = $assignments->first();

            // 2. Real-time states
            $realtimeStates = $realtimeRepo->getRealtimeStates([$employee->id]);
            $currentState = $realtimeStates->first();

            // 3. Transitions
            $transitions = $realtimeRepo->getBatchStateTransitions([$employee->id], $today);
            $timeByState = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
                ->map(fn ($group) => $group->sum('duration'));

            $states = [
                'current' => $currentState?->current_state ?? 'OFFLINE',
                'reason' => $currentState?->reason_code,
                'logged_seconds' => $timeByState->sum(),
                'talking' => $timeByState->get('TALKING', 0),
                'ready' => $timeByState->get('READY', 0),
                'not_ready' => $timeByState->get('NOT_READY', 0),
                'work' => $timeByState->get('WORK', 0),
                'lunch' => $timeByState->get('NOT_READY_LUNCH', 0) + $timeByState->get('NOT_READY_ALMUERZO', 0) + $timeByState->get('LUNCH', 0),
                'break' => $timeByState->get('NOT_READY_BREAK', 0) + $timeByState->get('NOT_READY_DESCANSO', 0) + $timeByState->get('BREAK', 0),
            ];

            // 4. Call stats
            $callStats = $realtimeRepo->getCallStatsForDate($today);
            $queuePerf = $realtimeRepo->getQueuePerformanceReport($today);

            // 5. Intraday activities
            $intradayEvents = $scheduleQueries->getUpcomingEvents([$employee->id], $today, 20);

            // 6. Hero KPIs (coverage, adherence, occupancy, sl, absenteeism, shrinkage)
            $employeeIds = [$employee->id];
            $heroKpis = $performanceService->getGlobalHeroKpis($now);

            // 7. Shrinkage personal
            $shrinkage = $performanceService->calculateShrinkage($employeeIds, $now);

            // 8. Attendance
            $exceptions = $scheduleQueries->getExceptionCount([$employee->id], $today);
        }

        return view('wfm::livewire.my-metrics', [
            'metrics' => $metrics,
            'employee' => $employee,
            'schedule' => $schedule,
            'states' => $states,
            'callStats' => $callStats,
            'queuePerf' => $queuePerf,
            'intradayEvents' => $intradayEvents,
            'shrinkage' => $shrinkage,
            'heroKpis' => $heroKpis,
            'currentDate' => $now,
            'hasExceptions' => ($exceptions ?? 0) > 0,
        ])->layout('layouts.app', ['title' => 'Mis Métricas']);
    }
}
