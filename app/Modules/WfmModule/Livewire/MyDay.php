<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire;

use App\Modules\OperationsModule\Services\PerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyDay extends Component
{
    public function render(
        TelemetryRealtimeRepositoryInterface $realtimeRepo,
        DashboardScheduleQueriesInterface $scheduleQueries,
        PerformanceService $performanceService,
    ) {
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return view('wfm::livewire.my-day', [
                'employeeData' => null,
                'targetEmployee' => null,
            ])->layout('layouts.app', ['title' => 'Mi Jornada']);
        }

        $targetEmployee = Employee::with('team', 'position')->find($employee->id);

        if (! $targetEmployee) {
            return view('wfm::livewire.my-day', [
                'employeeData' => null,
                'targetEmployee' => null,
            ])->layout('layouts.app', ['title' => 'Mi Jornada']);
        }

        $now = now();
        $today = $now->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        // Schedule
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

        // Realtime states
        $realtimeStates = $realtimeRepo->getRealtimeStates([$targetEmployee->id]);
        $currentState = $realtimeStates->first();
        $isConnected = $currentState && ! in_array($currentState->current_state, ['LOGOUT', 'OFFLINE', 'UNKNOWN']);

        // Transitions
        $transitions = $realtimeRepo->getBatchStateTransitions([$targetEmployee->id], $today);
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

        // Real entry
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

        // Calls
        $callStats = $realtimeRepo->getCallStatsForDate($today);
        $totalCalls = (int) ($callStats->total ?? 0);
        $handledCalls = (int) ($callStats->handled ?? 0);
        $sla = $totalCalls > 0 ? round(($handledCalls / $totalCalls) * 100, 1) : 0;

        // Hero KPIs
        $heroKpis = $performanceService->getGlobalHeroKpis($now);
        $adherence = $heroKpis['adherence']['value'] ?? '--';

        // Exceptions
        $exceptions = $scheduleQueries->getExceptionCount([$targetEmployee->id], $today);
        $hasExceptions = $exceptions > 0;

        // Upcoming events
        $events = $scheduleQueries->getUpcomingEvents([$targetEmployee->id], $today, 5);

        $employeeData = [
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
        ];

        return view('wfm::livewire.my-day', [
            'employeeData' => $employeeData,
            'targetEmployee' => $targetEmployee,
        ])->layout('layouts.app', ['title' => 'Mi Jornada']);
    }
}
