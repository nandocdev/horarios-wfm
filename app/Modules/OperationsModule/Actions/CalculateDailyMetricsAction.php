<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Actions;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Modules\OperationsModule\Models\QueueDailyMetric;
use Illuminate\Support\Facades\DB;

class CalculateDailyMetricsAction
{
    /**
     * Calcula y almacena las métricas diarias para la fecha dada.
     */
    public function execute(string $date): void
    {
        DB::transaction(function () use ($date) {
            $this->calculateQueueMetrics($date);
            $this->calculateAgentMetrics($date);
        });
    }

    private function calculateQueueMetrics(string $date): void
    {
        // El SLA threshold está hardcodeado a 20 según las consultas previas.
        $slThreshold = 20;

        $queueAggregates = CallRecord::query()
            ->select([
                'queue_id',
                DB::raw('COUNT(*) as offered_calls'),
                DB::raw('COUNT(*) FILTER (WHERE contact_disposition = 2) as handled_calls'),
                DB::raw('COUNT(*) FILTER (WHERE contact_disposition IN (1, 4, 13)) as abandoned_calls'),
                DB::raw("COUNT(*) FILTER (WHERE contact_disposition = 2 AND queue_time <= {$slThreshold}) as sl_calls"),
                DB::raw('COALESCE(SUM(talk_time) FILTER (WHERE contact_disposition = 2), 0) as total_talk_seconds'),
                DB::raw('COALESCE(SUM(work_time) FILTER (WHERE contact_disposition = 2), 0) as total_work_seconds'),
                DB::raw('0 as total_hold_seconds'),
                DB::raw('COALESCE(SUM(queue_time) FILTER (WHERE contact_disposition = 2), 0) as total_wait_seconds'),
                DB::raw('COALESCE(MAX(queue_time), 0) as max_wait_seconds'),
                DB::raw('COALESCE(MIN(queue_time), 0) as min_wait_seconds'),
                DB::raw('COALESCE(SUM(queue_time) FILTER (WHERE contact_disposition IN (1, 4, 13)), 0) as total_abandon_seconds'),
            ])
            ->whereDate('ivr_started_at', $date)
            ->whereNotNull('queue_id')
            ->groupBy('queue_id')
            ->get();

        foreach ($queueAggregates as $aggregate) {
            QueueDailyMetric::updateOrCreate(
                [
                    'queue_id' => $aggregate->queue_id,
                    'metric_date' => $date,
                ],
                [
                    'offered_calls' => (int) $aggregate->offered_calls,
                    'handled_calls' => (int) $aggregate->handled_calls,
                    'abandoned_calls' => (int) $aggregate->abandoned_calls,
                    'sl_calls' => (int) $aggregate->sl_calls,
                    'total_talk_seconds' => (int) $aggregate->total_talk_seconds,
                    'total_work_seconds' => (int) $aggregate->total_work_seconds,
                    'total_hold_seconds' => (int) $aggregate->total_hold_seconds,
                    'total_wait_seconds' => (int) $aggregate->total_wait_seconds,
                    'max_wait_seconds' => (int) $aggregate->max_wait_seconds,
                    'min_wait_seconds' => (int) $aggregate->min_wait_seconds,
                    'total_abandon_seconds' => (int) $aggregate->total_abandon_seconds,
                ]
            );
        }
    }

    private function calculateAgentMetrics(string $date): void
    {
        $agentAggregates = AgentCallPerformance::query()
            ->select([
                'employee_id',
                DB::raw('COUNT(*) as handled_calls'),
                DB::raw('COALESCE(SUM(talk_time), 0) as work_seconds'), // Wait, talk + work is work_seconds?
                DB::raw('COALESCE(SUM(work_time), 0) as acw_seconds'),
                DB::raw('COALESCE(SUM(hold_time), 0) as hold_seconds'),
            ])
            ->whereDate('start_time', $date)
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->get();

        foreach ($agentAggregates as $aggregate) {
            $talk = (int) $aggregate->work_seconds;
            $work = (int) $aggregate->acw_seconds;

            AgentDailyMetric::updateOrCreate(
                [
                    'employee_id' => $aggregate->employee_id,
                    'metric_date' => $date,
                ],
                [
                    'handled_calls' => (int) $aggregate->handled_calls,
                    'talk_seconds' => $talk,
                    'work_seconds' => $work,
                    'hold_seconds' => (int) $aggregate->hold_seconds,
                ]
            );
        }
    }
}
