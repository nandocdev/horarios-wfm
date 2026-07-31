<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Models\AgentIntervalMetric;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\Contracts\Schedules\ScheduleServiceInterface;
use App\Shared\Support\Metrics\RealtimeMetrics;
use App\Shared\Support\Metrics\ServiceQualityMetrics;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class GenerateAgentIntervalMetricsAction
{
    public function __construct(
        private readonly AgentPerformanceRepositoryInterface $performanceRepo,
        private readonly ScheduleServiceInterface $scheduleService,
    ) {}

    public function execute(int $employeeId, CarbonInterface $intervalStart, CarbonInterface $intervalEnd): ?AgentIntervalMetric
    {
        // Si el empleado no tiene turno en este intervalo, no generar métricas
        $scheduledSeconds = $this->calculateScheduledSeconds($employeeId, $intervalStart, $intervalEnd);
        if ($scheduledSeconds <= 0) {
            return null;
        }

        $stateDurations = $this->calculateStateDurations($employeeId, $intervalStart, $intervalEnd);
        $callMetrics = $this->calculateCallMetrics($employeeId, $intervalStart, $intervalEnd);

        $totalSeconds = $intervalStart->diffInSeconds($intervalEnd);
        $productiveSeconds = $stateDurations['talk'] + $stateDurations['hold'] + $stateDurations['wrap'];
        $loggedSeconds = $productiveSeconds + $stateDurations['ready'] + $stateDurations['not_ready'];

        // Occupancy: (Talk + Hold + ACW) / (Logged - Aux) × 100
        $occupancy = RealtimeMetrics::occupancy(
            (float) $stateDurations['talk'],
            (float) $stateDurations['hold'],
            (float) $stateDurations['wrap'],
            (float) $loggedSeconds,
            (float) $stateDurations['not_ready']
        );

        // Utilization: Productive / Scheduled × 100 (IND-024)
        $utilization = RealtimeMetrics::utilization(
            (float) $productiveSeconds / 60,
            (float) $scheduledSeconds / 60
        );

        // Adherence: Productive_in_Scheduled_State / Scheduled_Time × 100 (IND-025)
        // Para un intervalo de 15 min, la adherencia = tiempo productivo dentro del turno / tiempo programado
        $adherence = RealtimeMetrics::adherenceRate(
            (float) $productiveSeconds,
            (float) $scheduledSeconds
        );

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

    /**
     * Calcula los segundos programados para el empleado dentro del intervalo dado.
     * Retorna 0 si el empleado no tiene turno en ese intervalo.
     */
    private function calculateScheduledSeconds(int $employeeId, CarbonInterface $intervalStart, CarbonInterface $intervalEnd): int
    {
        $dayInfo = $this->scheduleService->getScheduleForEmployee($employeeId, $intervalStart);

        if ($dayInfo->is_off || ! $dayInfo->start_time || ! $dayInfo->end_time) {
            return 0;
        }

        $shiftStart = Carbon::parse($dayInfo->start_time)->setDate($intervalStart->year, $intervalStart->month, $intervalStart->day);
        $shiftEnd = Carbon::parse($dayInfo->end_time)->setDate($intervalStart->year, $intervalStart->month, $intervalStart->day);

        if ($shiftEnd->lessThan($shiftStart)) {
            $shiftEnd = $shiftEnd->addDay();
        }

        // Overlap: [max(intervalStart, shiftStart), min(intervalEnd, shiftEnd)]
        $overlapStart = max($intervalStart->getTimestamp(), $shiftStart->getTimestamp());
        $overlapEnd = min($intervalEnd->getTimestamp(), $shiftEnd->getTimestamp());

        return max(0, $overlapEnd - $overlapStart);
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
                $seconds = min(
                    abs($transitionTime->diffInSeconds($currentTime)),
                    abs($currentTime->diffInSeconds($end))
                );
                $this->addToDuration($durations, $currentState, (int) $seconds);
                $currentTime = $transitionTime;
            }

            $currentState = $this->mapState($transition->agent_state);
        }

        $remainingSeconds = abs($currentTime->diffInSeconds($end));
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
        return match (strtoupper(trim($agentState))) {
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
