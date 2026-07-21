<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\OperationsModule\Services\PerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\DailyOperatorReport;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class MyDay extends Component
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
        $next = Carbon::parse($this->selectedDate)->addDay();
        if ($next->lte(now())) {
            $this->selectedDate = $next->toDateString();
        }
    }

    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
        DashboardScheduleQueriesInterface $scheduleQueries,
        PerformanceService $performanceService,
    ) {
        $user = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return $this->emptyView();
        }

        $targetEmployee = Employee::with('team', 'position')->find($employee->id);

        if (! $targetEmployee) {
            return $this->emptyView();
        }

        $now = Carbon::parse($this->selectedDate);
        $today = $now->toDateString();
        $isToday = $today === now()->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        $employeeData = [];

        if ($isToday) {
            $employeeData = $this->buildFromRealtime(
                $targetEmployee,
                $today,
                $dayOfWeek,
                $realtimeRepo,
                $scheduleQueries,
                $performanceService,
                $now,
            );
        } else {
            $report = DailyOperatorReport::where('employee_id', $targetEmployee->id)
                ->where('report_date', $today)
                ->first();

            if ($report) {
                $employeeData = $this->buildFromReport($report, $targetEmployee, $scheduleQueries, $today);
            } else {
                $employeeData = $this->buildFromRealtime(
                    $targetEmployee,
                    $today,
                    $dayOfWeek,
                    $realtimeRepo,
                    $scheduleQueries,
                    $performanceService,
                    $now,
                );
            }
        }

        return view('wfm::livewire.my-day', [
            'employeeData' => $employeeData,
            'targetEmployee' => $targetEmployee,
        ])->layout('layouts.app', ['title' => 'Mi Jornada']);
    }

    private function emptyView(): View
    {
        return view('wfm::livewire.my-day', [
            'employeeData' => null,
            'targetEmployee' => null,
        ])->layout('layouts.app', ['title' => 'Mi Jornada']);
    }

    private function buildFromRealtime(
        Employee $targetEmployee,
        string $today,
        int $dayOfWeek,
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
        DashboardScheduleQueriesInterface $scheduleQueries,
        PerformanceService $performanceService,
        Carbon $now,
    ): array {
        $assignment = WeeklyScheduleAssignment::with('schedule')
            ->where('employee_id', $targetEmployee->id)
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

        $realtimeStates = $realtimeRepo->getRealtimeStates([$targetEmployee->id]);
        $currentState = $realtimeStates->first();
        $isConnected = $currentState && ! in_array($currentState->current_state, ['LOGOUT', 'OFFLINE', 'UNKNOWN']);

        $transitions = $realtimeRepo->getBatchStateTransitions([$targetEmployee->id], $today);

        return $this->buildEmployeeData(
            $targetEmployee, $today, $assignment, $transitions,
            $scheduleQueries, $performanceService, $now,
            $currentState, $isConnected,
            $schedEntry, $schedEnd, $lunchStart, $lunchEnd, $breakStart, $breakEnd,
        );
    }

    private function buildFromReport(
        DailyOperatorReport $report,
        Employee $targetEmployee,
        DashboardScheduleQueriesInterface $scheduleQueries,
        string $today,
    ): array {
        $events = $scheduleQueries->getUpcomingEvents([$targetEmployee->id], $today, 5);
        $adherence = null;

        return [
            'is_historical' => true,
            'name' => $targetEmployee->full_name,
            'team' => $targetEmployee->team?->name ?? '—',
            'current_state' => 'HISTORICAL',
            'reason' => null,
            'is_connected' => false,
            'scheduled_entry' => $report->scheduled_start?->format('H:i') ?? '--:--',
            'scheduled_end' => $report->scheduled_end?->format('H:i') ?? '--:--',
            'real_entry' => $report->real_entry?->format('H:i'),
            'entry_diff' => $report->entry_diff_minutes,
            'lunch_start' => $report->lunch_start?->format('H:i'),
            'lunch_end' => $report->lunch_end?->format('H:i'),
            'break_start' => $report->break_start?->format('H:i'),
            'break_end' => $report->break_end?->format('H:i'),
            'total_seconds' => $report->talk_seconds + $report->ready_seconds + $report->acw_seconds
                + $report->reserved_seconds + $report->not_ready_seconds
                + $report->lunch_seconds + $report->break_seconds + $report->offline_seconds,
            'productive_seconds' => $report->talk_seconds + $report->ready_seconds
                + $report->acw_seconds + $report->reserved_seconds,
            'occupancy' => $report->occupancy_pct,
            'talk' => $report->talk_seconds,
            'ready' => $report->ready_seconds,
            'acw' => $report->acw_seconds,
            'reserved' => $report->reserved_seconds,
            'lunch' => $report->lunch_seconds,
            'break' => $report->break_seconds,
            'not_ready' => $report->not_ready_seconds,
            'offline' => $report->offline_seconds,
            'total_calls' => $report->total_calls,
            'handled_calls' => $report->handled_calls,
            'sla' => $report->handled_calls > 0
                ? round(($report->handled_calls / max($report->total_calls, 1)) * 100, 1)
                : 0,
            'adherence' => $adherence,
            'has_exceptions' => $report->has_exceptions,
            'events' => $events->toArray(),
            'transitions' => [],
            'shrinkage' => null,
            'productivity_pct' => $report->productivity_pct,
            'avg_handle_time' => $report->avg_handle_time,
        ];
    }

    private function buildEmployeeData(
        Employee $targetEmployee,
        string $today,
        $assignment,
        $transitions,
        DashboardScheduleQueriesInterface $scheduleQueries,
        PerformanceService $performanceService,
        Carbon $now,
        $currentState,
        bool $isConnected,
        string $schedEntry,
        string $schedEnd,
        ?string $lunchStart,
        ?string $lunchEnd,
        ?string $breakStart,
        ?string $breakEnd,
    ): array {
        $timeByState = $transitions->groupBy(fn ($t) => strtoupper(trim($t->agent_state)))
            ->map(fn ($group) => $group->sum('duration'));

        $totalSeconds = $timeByState->sum();
        $talkSeconds = $timeByState->get('TALKING', 0);
        $readySeconds = $timeByState->get('READY', 0);
        $acwSeconds = $timeByState->get('WORK', 0) + $timeByState->get('ACW', 0);
        $reservedSeconds = $timeByState->get('RESERVED', 0);
        $productiveSeconds = $talkSeconds + $readySeconds + $acwSeconds + $reservedSeconds;
        $effectiveSeconds = $productiveSeconds + $readySeconds;
        $lunchSeconds = $timeByState->get('NOT_READY_LUNCH', 0) + $timeByState->get('NOT_READY_ALMUERZO', 0) + $timeByState->get('LUNCH', 0);
        $breakSeconds = $timeByState->get('NOT_READY_BREAK', 0) + $timeByState->get('NOT_READY_DESCANSO', 0) + $timeByState->get('BREAK', 0);
        $notReadySeconds = $timeByState->get('NOT_READY', 0);
        $offlineSeconds = $timeByState->get('LOGOUT', 0) + $timeByState->get('OFFLINE', 0);

        $occupancy = $effectiveSeconds > 0 ? round(($productiveSeconds / $effectiveSeconds) * 100, 1) : 0;

        $firstTransition = $transitions->sortBy('transition_time')->first();
        $realEntry = $firstTransition?->transition_time
            ? Carbon::parse($firstTransition->transition_time)->format('H:i')
            : null;

        $entryDiff = null;
        if ($realEntry && $assignment?->start_time) {
            $sched = Carbon::parse($assignment->start_time);
            $real = Carbon::parse($firstTransition->transition_time);
            $entryDiff = (int) $sched->diffInMinutes($real, false);
        }

        $empIds = [$targetEmployee->id];
        $callStats = app(TelemetryRealtimeRepositoryInterface::class)->getCallStatsForDate($today, $empIds);
        $totalCalls = (int) ($callStats->total ?? 0);
        $handledCalls = (int) ($callStats->handled ?? 0);
        $sla = $totalCalls > 0 ? round(($handledCalls / $totalCalls) * 100, 1) : 0;

        $heroKpis = $performanceService->getGlobalHeroKpis($now);
        $adherence = $heroKpis['adherence']['value'] ?? '--';
        $exceptions = $scheduleQueries->getExceptionCount([$targetEmployee->id], $today);
        $hasExceptions = $exceptions > 0;
        $events = $scheduleQueries->getUpcomingEvents([$targetEmployee->id], $today, 5);

        $shrinkage = $performanceService->calculateShrinkage([$targetEmployee->id], $now);

        return [
            'is_historical' => false,
            'name' => $targetEmployee->full_name,
            'team' => $targetEmployee->team?->name ?? '—',
            'current_state' => $currentState?->current_state ?? 'OFFLINE',
            'reason' => $currentState?->reason_code,
            'is_connected' => $isConnected,
            'scheduled_entry' => $schedEntry,
            'scheduled_end' => $schedEnd,
            'real_entry' => $realEntry,
            'entry_diff' => $entryDiff,
            'lunch_start' => $lunchStart,
            'lunch_end' => $lunchEnd,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
            'total_seconds' => $totalSeconds,
            'productive_seconds' => $productiveSeconds,
            'occupancy' => $occupancy,
            'talk' => $talkSeconds,
            'ready' => $readySeconds,
            'acw' => $acwSeconds,
            'reserved' => $reservedSeconds,
            'lunch' => $lunchSeconds,
            'break' => $breakSeconds,
            'not_ready' => $notReadySeconds,
            'offline' => $offlineSeconds,
            'total_calls' => $totalCalls,
            'handled_calls' => $handledCalls,
            'sla' => $sla,
            'adherence' => $adherence,
            'has_exceptions' => $hasExceptions,
            'events' => $events->toArray(),
            'transitions' => $transitions->sortByDesc('transition_time')->take(20)->values()->toArray(),
            'shrinkage' => $shrinkage,
            'productivity_pct' => $totalSeconds > 0
                ? round(($productiveSeconds / $totalSeconds) * 100, 1)
                : 0,
            'avg_handle_time' => $handledCalls > 0
                ? round(($talkSeconds + $acwSeconds) / $handledCalls, 1)
                : null,
        ];
    }
}
