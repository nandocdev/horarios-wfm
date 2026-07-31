<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\Support\Metrics\RealtimeMetrics;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class GenerateAgentIntervalMetricsAction
{
    public function __construct(
        private readonly AgentPerformanceRepositoryInterface $performanceRepo,
    ) {}

    public function execute(int $employeeId, CarbonInterface $intervalStart, CarbonInterface $intervalEnd): AgentIntervalMetric
    {
        $stateDurations = $this->calculateStateDurations($employeeId, $intervalStart, $intervalEnd);
        $callMetrics = $this->calculateCallMetrics($employeeId, $intervalStart, $intervalEnd);

        $totalSeconds = $intervalStart->diffInSeconds($intervalEnd);
        $productiveSeconds = $stateDurations['talk'] + $stateDurations['hold'] + $stateDurations['wrap'];
        $availableSeconds = $productiveSeconds + $stateDurations['ready'];
        $loggedSeconds = $productiveSeconds + $stateDurations['ready'] + $stateDurations['not_ready'];

        $occupancy = RealtimeMetrics::occupancy(
            (float) $stateDurations['talk'],
            (float) $stateDurations['hold'],
            (float) $stateDurations['wrap'],
            (float) $loggedSeconds,
            (float) $stateDurations['not_ready']
        );

        $utilization = $totalSeconds > 0
            ? round(($loggedSeconds / $totalSeconds) * 100, 2)
            : 0.0;

        $adherence = $totalSeconds > 0
            ? round(($productiveSeconds / $totalSeconds) * 100, 2)
            : 0.0;

        $aht = ServiceQualityMetrics::aht(
            (float) $stateDurations['talk'],
            (float) $stateDurations['hold'],
            (float) $stateDurations['wrap'],
            $callMetrics['calls_handled']
        );

        return AgentIntervalMetric::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'interval_start' => $intervalStart,
            ],
            [
                'interval_end' => $intervalEnd,
                'talk_seconds' => $stateDurations['talk'],
                'hold_seconds' => $stateDurations['hold'],
                'ready_seconds' => $stateDurations['ready'],
                'not_ready_seconds' => $stateDurations['not_ready'],
                'wrap_seconds' => $stateDurations['wrap'],
                'calls_handled' => $callMetrics['calls_handled'],
                'aht_seconds' => $aht,
                'occupancy' => $occupancy,
                'utilization' => $utilization,
                'adherence' => $adherence,
                'queue_distribution' => $callMetrics['queue_distribution'],
            ]
        );
    }

    private function calculateStateDurations(int $employeeId, CarbonInterface $start, CarbonInterface $end): array
    {
        $transitions = $this->getTransitionsIncludingPreceding($employeeId, $start, $end);

        $durations = [
            'talk' => 0,
            'hold' => 0,
            'ready' => 0,
            'not_ready' => 0,
            'wrap' => 0,
        ];

        if ($transitions->isEmpty()) {
            return $durations;
        }

        $currentTime = $start->copy();
        $currentState = $this->mapState($transitions->first()->agent_state);
        $transitions->shift();

        foreach ($transitions as $transition) {
            $transitionTime = $transition->transition_time;

            if ($transitionTime > $currentTime) {
                $seconds = min($transitionTime->diffInSeconds($currentTime), $currentTime->diffInSeconds($end));
                $this->addToDuration($durations, $currentState, (int) $seconds);
                $currentTime = $transitionTime;
            }

            $currentState = $this->mapState($transition->agent_state);
        }

        $remainingSeconds = $currentTime->diffInSeconds($end);
        if ($remainingSeconds > 0) {
            $this->addToDuration($durations, $currentState, (int) $remainingSeconds);
        }

        return $durations;
    }

    private function getTransitionsIncludingPreceding(int $employeeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        $preceding = AgentStateTransition::where('employee_id', $employeeId)
            ->where('transition_time', '<', $start)
            ->orderByDesc('transition_time')
            ->limit(1)
            ->get();

        $within = AgentStateTransition::where('employee_id', $employeeId)
            ->where('transition_time', '>=', $start)
            ->where('transition_time', '<', $end)
            ->orderBy('transition_time')
            ->get();

        if ($preceding->isNotEmpty() && $within->isNotEmpty()) {
            $within->prepend($preceding->first());
        } elseif ($preceding->isNotEmpty()) {
            $within = $preceding;
        }

        return $within;
    }

    private function mapState(string $agentState): string
    {
        return match (strtoupper($agentState)) {
            'TALKING' => 'talk',
            'HOLD' => 'hold',
            'READY', 'RESERVED' => 'ready',
            'NOT_READY', 'AUX' => 'not_ready',
            'WORK' => 'wrap',
            default => 'not_ready',
        };
    }

    private function addToDuration(array &$durations, string $state, int $seconds): void
    {
        if (isset($durations[$state])) {
            $durations[$state] += $seconds;
        }
    }

    private function calculateCallMetrics(int $employeeId, CarbonInterface $start, CarbonInterface $end): array
    {
        $calls = $this->performanceRepo->getCallRecordsInInterval($employeeId, $start, $end);

        $callsHandled = $calls->count();
        $talkSeconds = (int) $calls->sum('talk_time');
        $holdSeconds = (int) $calls->sum('hold_time');
        $wrapSeconds = (int) $calls->sum('work_time');

        $queueDist = [];
        if ($callsHandled > 0) {
            $queueGroups = $calls->groupBy('csq_name');
            foreach ($queueGroups as $csqName => $group) {
                $count = $group->count();
                $queueDist[] = [
                    'csq_name' => $csqName,
                    'calls' => $count,
                    'talk_seconds' => (int) $group->sum('talk_time'),
                    'hold_seconds' => (int) $group->sum('hold_time'),
                    'wrap_seconds' => (int) $group->sum('work_time'),
                ];
            }
        }

        return [
            'calls_handled' => $callsHandled,
            'talk_seconds' => $talkSeconds,
            'hold_seconds' => $holdSeconds,
            'wrap_seconds' => $wrapSeconds,
            'queue_distribution' => $queueDist,
        ];
    }
}
