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
    public string $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->toDateString();
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
        DashboardScheduleQueriesInterface $scheduleQueries,
        PerformanceService $performanceService,
    ) {
        $user = auth()->user();
        $employee = $user->employee ? Employee::with('team', 'position')->find($user->employee->id) : null;
        $now = Carbon::parse($this->selectedDate);
        $today = $now->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        $scheduleData = [];
        $states = [];
        $callStats = null;
        $queuePerf = [];
        $intradayEvents = [];
        $shrinkage = 0;
        $heroKpis = [];

        if ($employee) {
            // Schedule
            $assignment = WeeklyScheduleAssignment::with('schedule')
                ->where('employee_id', $employee->id)
                ->where('day_of_week', $dayOfWeek)
                ->whereHas('weeklySchedule', fn ($q) => $q
                    ->where('week_start_date', '<=', $today)
                    ->where('week_end_date', '>=', $today)
                )
                ->first();

            $schedEntry = $assignment?->start_time ? Carbon::parse($assignment->start_time)->format('H:i') : '--:--';
            $schedEnd = $assignment?->end_time ? Carbon::parse($assignment->end_time)->format('H:i') : '--:--';
            $lunchStart = $assignment?->lunch_start_time ? Carbon::parse($assignment->lunch_start_time)->format('H:i') : null;
            $lunchEnd = $assignment?->lunch_end_time ? Carbon::parse($assignment->lunch_end_time)->format('H:i') : null;
            $breakStart = $assignment?->break_start_time ? Carbon::parse($assignment->break_start_time)->format('H:i') : null;
            $breakEnd = $assignment?->break_end_time ? Carbon::parse($assignment->break_end_time)->format('H:i') : null;

            // Realtime states
            $realtimeStates = $realtimeRepo->getRealtimeStates([$employee->id]);
            $currentState = $realtimeStates->first();
            $isConnected = $currentState && ! in_array($currentState->current_state, ['LOGOUT', 'OFFLINE', 'UNKNOWN']);

            // Transitions for time breakdown
            $transitions = $realtimeRepo->getBatchStateTransitions([$employee->id], $today);
            $timeByState = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
                ->map(fn ($group) => $group->sum('duration'));

            $totalSeconds = $timeByState->sum();
            $talkSeconds = $timeByState->get('TALKING', 0);
            $readySeconds = $timeByState->get('READY', 0);
            $acwSeconds = $timeByState->get('WORK', 0) + $timeByState->get('ACW', 0);
            $reservedSeconds = $timeByState->get('RESERVED', 0);
            $productiveSeconds = $talkSeconds + $readySeconds + $acwSeconds + $reservedSeconds;
            $notReadySeconds = $timeByState->get('NOT_READY', 0);
            $lunchSeconds = $timeByState->get('NOT_READY_LUNCH', 0) + $timeByState->get('NOT_READY_ALMUERZO', 0) + $timeByState->get('LUNCH', 0);
            $breakSeconds = $timeByState->get('NOT_READY_BREAK', 0) + $timeByState->get('NOT_READY_DESCANSO', 0) + $timeByState->get('BREAK', 0);
            $offlineSeconds = $timeByState->get('LOGOUT', 0) + $timeByState->get('OFFLINE', 0);

            // First transition of the day = real entry
            $firstTransition = $transitions->sortBy('transition_time')->first();
            $realEntry = $firstTransition?->transition_time
                ? Carbon::parse($firstTransition->transition_time)->format('H:i')
                : null;

            // Calculate entry difference
            $entryDiff = null;
            if ($realEntry && $assignment?->start_time) {
                $sched = Carbon::parse($assignment->start_time);
                $real = Carbon::parse($firstTransition->transition_time);
                $entryDiff = (int) $sched->diffInMinutes($real, false);
            }

            $states = [
                'current' => $currentState?->current_state ?? 'OFFLINE',
                'reason' => $currentState?->reason_code,
                'is_connected' => $isConnected,
                'total_seconds' => $totalSeconds,
                'productive_seconds' => $productiveSeconds,
                'talk' => $talkSeconds,
                'ready' => $readySeconds,
                'acw' => $acwSeconds,
                'reserved' => $reservedSeconds,
                'not_ready' => $notReadySeconds,
                'lunch' => $lunchSeconds,
                'break' => $breakSeconds,
                'offline' => $offlineSeconds,
                'productivity_pct' => $totalSeconds > 0 ? round(($productiveSeconds / $totalSeconds) * 100, 1) : 0,
                'real_entry' => $realEntry,
                'entry_diff' => $entryDiff,
                'scheduled_entry' => $schedEntry,
                'scheduled_end' => $schedEnd,
                'lunch_start' => $lunchStart,
                'lunch_end' => $lunchEnd,
                'break_start' => $breakStart,
                'break_end' => $breakEnd,
            ];

            // Call stats
            $callStats = $realtimeRepo->getCallStatsForDate($today);
            $queuePerf = $realtimeRepo->getQueuePerformanceReport($today);

            // Intraday activities
            $intradayEvents = $scheduleQueries->getUpcomingEvents([$employee->id], $today, 20);

            // Hero KPIs
            $heroKpis = $performanceService->getGlobalHeroKpis($now);

            // Shrinkage
            $shrinkage = $performanceService->calculateShrinkage([$employee->id], $now);
        }

        return view('wfm::livewire.my-metrics', [
            'employee' => $employee,
            'currentDate' => $now,
            'states' => $states,
            'callStats' => $callStats,
            'queuePerf' => $queuePerf,
            'intradayEvents' => $intradayEvents,
            'heroKpis' => $heroKpis,
            'shrinkage' => $shrinkage,
            'transitions' => $transitions ?? collect(),
        ])->layout('layouts.app', ['title' => 'Mis Métricas']);
    }
}
