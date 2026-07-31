<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Services;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\OperationsModule\Services\PerformanceService;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\DailyOperatorReport;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Contracts\Schedules\DashboardScheduleQueriesInterface;
use App\Shared\Contracts\Telemetry\TelemetryRealtimeRepositoryInterface;
use App\Shared\Contracts\WfmModule\ExpectedAgentStateInterface;
use App\Shared\Support\Metrics\RealtimeMetrics;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MyDayService
{
    /**
     * @var array<string, array>
     */
    protected array $cache = [];

    public function __construct(
        protected TelemetryRealtimeRepositoryInterface $realtimeRepo,
        protected DashboardScheduleQueriesInterface $scheduleQueries,
        protected PerformanceService $performanceService,
        protected ExpectedAgentStateInterface $expectedState
    ) {}

    public function getEmployeeData(Employee $targetEmployee, string $dateString): array
    {
        $key = "{$targetEmployee->id}_{$dateString}";

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $now = Carbon::parse($dateString);
        $today = $now->toDateString();
        $isToday = $today === now()->toDateString();
        $dayOfWeek = $now->dayOfWeekIso;

        if ($isToday) {
            $employeeData = $this->buildFromRealtime(
                $targetEmployee,
                $today,
                $dayOfWeek,
                $now
            );
        } else {
            $report = DailyOperatorReport::where('employee_id', $targetEmployee->id)
                ->where('report_date', $today)
                ->first();

            if ($report) {
                $employeeData = $this->buildFromReport($report, $targetEmployee, $today);
            } else {
                $employeeData = $this->buildFromRealtime(
                    $targetEmployee,
                    $today,
                    $dayOfWeek,
                    $now
                );
            }
        }

        $intervals = $this->buildAdherenceIntervals(
            $targetEmployee,
            $today,
            $dayOfWeek,
            $employeeData['transitions'] ?? collect(),
            $now
        );

        $employeeData['adherence_intervals'] = $intervals;

        $onTrack = collect($intervals)->where('state', 'on_track')->count();
        $offTrack = collect($intervals)->where('state', 'off_track')->count();
        $totalTracked = $onTrack + $offTrack;
        $employeeData['adherence'] = $totalTracked > 0
            ? round(($onTrack / $totalTracked) * 100, 1)
            : 0;

        $this->cache[$key] = $employeeData;

        return $employeeData;
    }

    private function buildFromRealtime(
        Employee $targetEmployee,
        string $today,
        int $dayOfWeek,
        Carbon $now
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

        $realtimeStates = $this->realtimeRepo->getRealtimeStates([$targetEmployee->id]);
        $currentState = $realtimeStates->first();
        $isConnected = $currentState && ! in_array($currentState->current_state, ['LOGOUT', 'OFFLINE', 'UNKNOWN']);

        $transitions = $this->realtimeRepo->getBatchStateTransitions([$targetEmployee->id], $today);

        return $this->buildEmployeeData(
            $targetEmployee, $today, $assignment, $transitions,
            $now, $currentState, $isConnected,
            $schedEntry, $schedEnd, $lunchStart, $lunchEnd, $breakStart, $breakEnd
        );
    }

    private function buildFromReport(
        DailyOperatorReport $report,
        Employee $targetEmployee,
        string $today
    ): array {
        $events = $this->scheduleQueries->getUpcomingEvents([$targetEmployee->id], $today, 5);
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
            'avg_talk_time' => ServiceQualityMetrics::ahtComponents($report->talk_seconds, 0, $report->acw_seconds, $report->handled_calls)['talk'],
            'avg_acw_time' => ServiceQualityMetrics::ahtComponents($report->talk_seconds, 0, $report->acw_seconds, $report->handled_calls)['acw'],
            'aux_seconds' => $report->lunch_seconds + $report->break_seconds + $report->not_ready_seconds,
            'not_ready_by_reason' => [],
            'calls_by_queue' => [],
            'call_scatter_data' => [],
            'call_scatter_stats' => ['max' => 0, 'min' => 0, 'mean' => 0, 'std' => 0],
            'call_scatter_x_min' => 300,
            'call_scatter_x_max' => 900,
            'timeline_start' => $report->scheduled_start?->format('H:i') ?? '06:00',
            'timeline_end' => $report->scheduled_end?->format('H:i') ?? '18:00',
            'real_end' => null,
            'end_diff' => null,
            'lunch_diff' => null,
            'break_diff' => null,
            'first_lunch_time' => null,
            'first_break_time' => null,
            'intraday_activities' => [],
        ];
    }

    private function buildEmployeeData(
        Employee $targetEmployee,
        string $today,
        $assignment,
        $transitions,
        Carbon $now,
        $currentState,
        bool $isConnected,
        string $schedEntry,
        string $schedEnd,
        ?string $lunchStart,
        ?string $lunchEnd,
        ?string $breakStart,
        ?string $breakEnd
    ): array {
        $timeByState = $transitions->groupBy(function ($t) {
            $state = strtoupper(trim($t->agent_state ?? ''));
            $reason = strtoupper(trim($t->reason_code ?? ''));
            if ($state === 'NOT READY' && $reason) {
                return $state.'_'.$reason;
            }

            return $state;
        })->map(fn ($group) => $group->sum('duration'));

        $totalSeconds = $timeByState->sum();
        $talkSeconds = $timeByState->get('TALKING', 0);
        $readySeconds = $timeByState->get('READY', 0);
        $acwSeconds = $timeByState->get('WORK', 0) + $timeByState->get('ACW', 0);
        $reservedSeconds = $timeByState->get('RESERVED', 0);
        $handleSeconds = $talkSeconds + $acwSeconds + $reservedSeconds;

        $lunchSeconds = $timeByState->get('NOT READY_LUNCH', 0) + $timeByState->get('NOT READY_ALMUERZO', 0) + $timeByState->get('LUNCH', 0);
        $breakSeconds = $timeByState->get('NOT READY_BREAK', 0) + $timeByState->get('NOT READY_DESCANSO', 0) + $timeByState->get('BREAK', 0);

        $notReadySeconds = $timeByState->filter(function ($val, $key) {
            return str_starts_with($key, 'NOT READY');
        })->sum();

        $offlineSeconds = $timeByState->get('LOGOUT', 0) + $timeByState->get('OFFLINE', 0);
        $productiveSeconds = $handleSeconds + $readySeconds;
        $occupancy = RealtimeMetrics::occupancy(
            $talkSeconds,
            0,
            $acwSeconds,
            $totalSeconds - $offlineSeconds,
            $notReadySeconds + $lunchSeconds + $breakSeconds
        );

        $sortedTransitions = $transitions->sortBy('transition_time');
        $firstTransition = $sortedTransitions->first();
        $lastTransition = $sortedTransitions->last();

        $realEntry = $firstTransition?->transition_time
            ? Carbon::parse($firstTransition->transition_time)->format('H:i')
            : null;

        $entryDiff = null;
        if ($realEntry && $assignment?->start_time) {
            $sched = Carbon::parse($assignment->start_time);
            $real = Carbon::parse($firstTransition->transition_time);
            $entryDiff = (int) $sched->diffInMinutes($real, false);
        }

        $realEnd = null;
        $endDiff = null;
        if ($lastTransition && $assignment?->end_time) {
            $realEndTime = Carbon::parse($lastTransition->transition_time);
            $realEnd = $realEndTime->format('H:i');
            $schedEndTime = Carbon::parse($assignment->end_time);
            $endDiff = (int) $schedEndTime->diffInMinutes($realEndTime, false);
        }

        $empIds = [$targetEmployee->id];
        $callStats = $this->realtimeRepo->getCallStatsForDate($today, $empIds);
        $totalCalls = (int) ($callStats->total ?? 0);
        $handledCalls = (int) ($callStats->handled ?? 0);
        $sla = $totalCalls > 0 ? round(($handledCalls / $totalCalls) * 100, 1) : 0;

        $heroKpis = $this->performanceService->getGlobalHeroKpis($now);
        $adherence = $heroKpis['adherence']['value'] ?? '--';
        $exceptions = $this->scheduleQueries->getExceptionCount([$targetEmployee->id], $today);
        $hasExceptions = $exceptions > 0;
        $events = $this->scheduleQueries->getUpcomingEvents([$targetEmployee->id], $today, 5);

        $shrinkage = $this->performanceService->calculateShrinkage([$targetEmployee->id], $now);

        $ahtComponents = ServiceQualityMetrics::ahtComponents($talkSeconds, 0, $acwSeconds, $handledCalls);
        $avgTalkTime = $ahtComponents['talk'];
        $avgAcwTime = $ahtComponents['acw'];
        $avgHandleTime = $ahtComponents['aht'];

        $notReadyByReason = $transitions
            ->filter(function ($t) {
                return strtoupper(trim($t->agent_state ?? '')) === 'NOT READY';
            })
            ->groupBy(fn ($t) => strtoupper(trim($t->reason_code ?? 'SIN_MOTIVO')))
            ->map(fn ($group) => $group->sum('duration'))
            ->sortDesc()
            ->toArray();

        $agentCalls = AgentCallPerformance::where('employee_id', $targetEmployee->id)
            ->whereDate('start_time', $today)
            ->whereNotNull('talk_time')
            ->where('talk_time', '>', 0)
            ->orderBy('start_time')
            ->get(['start_time', 'talk_time', 'csq_name']);

        $callsByQueue = $this->realtimeRepo->getQueuePerformanceReport($today, [$targetEmployee->id]);

        $queueStats = collect($agentCalls)
            ->groupBy(fn ($c) => $c->csq_name ?? 'Sin Cola')
            ->map(function ($calls) {
                $talkTimes = $calls->pluck('talk_time')->toArray();
                $count = count($talkTimes);
                $mean = $count > 0 ? array_sum($talkTimes) / $count : 0;
                $variance = 0;
                if ($count > 1) {
                    $variance = array_sum(array_map(fn ($x) => pow($x - $mean, 2), $talkTimes)) / ($count - 1);
                }

                return [
                    'max' => $count > 0 ? max($talkTimes) : 0,
                    'min' => $count > 0 ? min($talkTimes) : 0,
                    'mean' => round($mean, 1),
                    'std' => round(sqrt($variance), 1),
                ];
            });

        $callsByQueue = $callsByQueue->map(function ($q) use ($queueStats) {
            $stats = $queueStats->get($q->queue_name ?? 'Sin Cola');
            $q->max_talk = $stats['max'] ?? 0;
            $q->min_talk = $stats['min'] ?? 0;
            $q->mean_talk = $stats['mean'] ?? 0;
            $q->std_talk = $stats['std'] ?? 0;

            return $q;
        });

        $shiftStart = $assignment?->start_time
            ? Carbon::parse($assignment->start_time)->setDateFrom(Carbon::parse($today))
            : Carbon::parse($today)->addHours(6);

        $shiftEnd = $assignment?->end_time
            ? Carbon::parse($assignment->end_time)->setDateFrom(Carbon::parse($today))
            : $shiftStart->copy()->addHours(8);

        $callScatterData = collect($agentCalls)
            ->groupBy(fn ($c) => $c->csq_name ?? 'Sin Cola')
            ->map(fn ($calls, $queueName) => [
                'name' => $queueName,
                'data' => $calls->map(fn ($call) => [
                    'x' => Carbon::parse($call->start_time)->getTimestamp() * 1000,
                    'y' => (int) $call->talk_time,
                    't' => Carbon::parse($call->start_time)->timezone('America/Panama')->format('H:i'),
                ])->values()->toArray(),
            ])->values()->toArray();

        $talkTimes = collect($agentCalls)->pluck('talk_time')->filter(fn ($t) => $t > 0)->toArray();
        $talkCount = count($talkTimes);
        $talkMax = $talkCount > 0 ? max($talkTimes) : 0;
        $talkMin = $talkCount > 0 ? min($talkTimes) : 0;
        $talkMean = $talkCount > 0 ? array_sum($talkTimes) / $talkCount : 0;

        $talkVariance = 0;
        if ($talkCount > 1) {
            $talkVariance = array_sum(array_map(fn ($x) => pow($x - $talkMean, 2), $talkTimes)) / ($talkCount - 1);
        }
        $talkStdDev = sqrt($talkVariance);

        $callScatterStats = [
            'max' => (int) $talkMax,
            'min' => (int) $talkMin,
            'mean' => round($talkMean, 1),
            'std' => round($talkStdDev, 1),
        ];

        $firstLunch = $transitions
            ->filter(function ($t) {
                $state = strtoupper(trim($t->agent_state ?? ''));
                $reason = strtoupper(trim($t->reason_code ?? ''));

                return in_array($state, ['LUNCH', 'NOT_READY_LUNCH', 'NOT_READY_ALMUERZO']) ||
                       ($state === 'NOT READY' && in_array($reason, ['LUNCH', 'ALMUERZO']));
            })
            ->sortBy('transition_time')
            ->first();

        $firstBreak = $transitions
            ->filter(function ($t) {
                $state = strtoupper(trim($t->agent_state ?? ''));
                $reason = strtoupper(trim($t->reason_code ?? ''));

                return in_array($state, ['BREAK', 'NOT_READY_BREAK', 'NOT_READY_DESCANSO']) ||
                       ($state === 'NOT READY' && in_array($reason, ['BREAK', 'DESCANSO']));
            })
            ->sortBy('transition_time')
            ->first();

        $lunchDiff = null;
        if ($firstLunch && $assignment?->lunch_start_time) {
            $schedLunch = Carbon::parse($assignment->lunch_start_time);
            $realLunch = Carbon::parse($firstLunch->transition_time);
            $lunchDiff = (int) $schedLunch->diffInMinutes($realLunch, false);
        }

        $breakDiff = null;
        if ($firstBreak && $assignment?->break_start_time) {
            $schedBreak = Carbon::parse($assignment->break_start_time);
            $realBreak = Carbon::parse($firstBreak->transition_time);
            $breakDiff = (int) $schedBreak->diffInMinutes($realBreak, false);
        }

        $intradayActivities = IntradayActivity::with('activityType')
            ->where('employee_id', $targetEmployee->id)
            ->whereDate('created_at', $today)
            ->get()
            ->map(fn ($a) => [
                'name' => $a->activityType?->name ?? 'Actividad',
                'start' => $a->getRangeStart()?->format('H:i'),
                'end' => $a->getRangeEnd()?->format('H:i'),
            ]);

        $hasRecentActivity = $productiveSeconds > 0 || $talkSeconds > 0;
        $disconnectedWithActivity = ! $isConnected && $hasRecentActivity;

        return [
            'is_historical' => false,
            'name' => $targetEmployee->full_name,
            'team' => $targetEmployee->team?->name ?? '—',
            'current_state' => $currentState?->current_state ?? 'OFFLINE',
            'reason' => $currentState?->reason_code,
            'is_connected' => $isConnected,
            'disconnected_with_activity' => $disconnectedWithActivity,
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
            'productivity_pct' => RealtimeMetrics::productivity($productiveSeconds, $totalSeconds - $offlineSeconds),
            'avg_handle_time' => $avgHandleTime > 0 ? $avgHandleTime : null,
            'avg_talk_time' => $avgTalkTime,
            'avg_acw_time' => $avgAcwTime,
            'aux_seconds' => $lunchSeconds + $breakSeconds + $notReadySeconds,
            'not_ready_by_reason' => $notReadyByReason,
            'calls_by_queue' => $callsByQueue->filter(fn ($q) => ($q->total_offered ?? 0) > 0 || ($q->handled ?? 0) > 0)->values(),
            'call_scatter_data' => $callScatterData,
            'call_scatter_stats' => $callScatterStats,
            'call_scatter_x_min' => $shiftStart->copy()->subHour()->getTimestamp() * 1000,
            'call_scatter_x_max' => $shiftEnd->copy()->addHour()->getTimestamp() * 1000,
            'timeline_start' => $assignment?->start_time ? Carbon::parse($assignment->start_time)->format('H:i') : '06:00',
            'timeline_end' => $assignment?->end_time ? Carbon::parse($assignment->end_time)->format('H:i') : '18:00',
            'real_end' => $realEnd,
            'end_diff' => $endDiff,
            'lunch_diff' => $lunchDiff,
            'break_diff' => $breakDiff,
            'first_lunch_time' => $firstLunch?->transition_time?->format('H:i'),
            'first_break_time' => $firstBreak?->transition_time?->format('H:i'),
            'intraday_activities' => $intradayActivities,
        ];
    }

    private function buildAdherenceIntervals(
        Employee $targetEmployee,
        string $today,
        int $dayOfWeek,
        array|Collection $transitions,
        Carbon $now
    ): array {
        $assignment = WeeklyScheduleAssignment::with('schedule')
            ->where('employee_id', $targetEmployee->id)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('weeklySchedule', fn ($q) => $q
                ->where('week_start_date', '<=', $today)
                ->where('week_end_date', '>=', $today)
            )
            ->first();

        if (! $assignment || ! $assignment->start_time || ! $assignment->end_time) {
            return [];
        }

        $todayCarbon = Carbon::parse($today);
        $start = Carbon::instance($assignment->start_time)->setDateFrom($todayCarbon);
        $end = Carbon::instance($assignment->end_time)->setDateFrom($todayCarbon);
        $intervals = [];

        $windowStart = $start->copy();
        while ($windowStart->lt($end)) {
            $windowEnd = $windowStart->copy()->addMinutes(30);
            if ($windowEnd->gt($end)) {
                $windowEnd = $end->copy();
            }

            $expected = $this->expectedState->execute((int) $targetEmployee->id, $windowStart);
            $expectedType = $expected['type'] ?? 'OFF';

            $actualState = $this->getActualStateForWindow($transitions, $windowStart, $windowEnd);

            $isAdherent = RealtimeMetrics::checkAdherence($actualState, $expectedType);

            $intervals[] = [
                'time' => $windowStart->format('H:i'),
                'expected' => $expectedType,
                'expected_label' => $expected['label'] ?? '',
                'actual' => $actualState,
                'is_adherent' => $isAdherent,
                'state' => match (true) {
                    $expectedType === 'OFF' && $actualState === 'OFFLINE' => 'off',
                    $isAdherent => 'on_track',
                    $windowStart->isFuture() => 'pending',
                    default => 'off_track',
                },
            ];

            $windowStart->addMinutes(30);
        }

        return $intervals;
    }

    private function getActualStateForWindow(Collection|array $transitions, Carbon|CarbonInterface $windowStart, Carbon|CarbonInterface $windowEnd): string
    {
        $windowStart = Carbon::instance($windowStart);
        $windowEnd = Carbon::instance($windowEnd);
        $windowTransitions = collect($transitions)->filter(function ($t) use ($windowStart, $windowEnd) {
            $tTime = Carbon::parse($t['transition_time'] ?? $t->transition_time ?? now());

            return $tTime->between($windowStart, $windowEnd);
        });

        if ($windowTransitions->isEmpty()) {
            $totalDuration = $windowStart->diffInSeconds($windowEnd);
            $duration = 0;
            $state = 'UNKNOWN';
            foreach ($transitions as $t) {
                $tDuration = (int) ($t['duration'] ?? $t->duration ?? 0);
                $tState = strtoupper($t['agent_state'] ?? $t->agent_state ?? '');
                $duration += $tDuration;
                if ($tDuration > 0) {
                    $state = $tState;
                }
            }

            if ($duration > ($totalDuration / 2)) {
                return $state;
            }

            return 'UNKNOWN';
        }

        $dominantState = $windowTransitions->sortByDesc(function ($t) {
            return (int) ($t['duration'] ?? $t->duration ?? 0);
        })->first();

        return strtoupper($dominantState['agent_state'] ?? $dominantState->agent_state ?? 'UNKNOWN');
    }
}
