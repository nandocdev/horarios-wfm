<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Repositories;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use App\Shared\DTOs\Operations\AgentCallRecordDTO;
use App\Shared\DTOs\Operations\AgentDailyMetricDTO;
use App\Shared\DTOs\Operations\AgentStateTransitionDTO;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class EloquentAgentPerformanceRepository implements AgentPerformanceRepositoryInterface
{
    public function getCallRecords(int $employeeId, CarbonInterface $date): Collection
    {
        return AgentCallPerformance::where('employee_id', $employeeId)
            ->whereDate('start_time', $date->toDateString())
            ->orderBy('start_time')
            ->get()
            ->map(fn (AgentCallPerformance $record): AgentCallRecordDTO => new AgentCallRecordDTO(
                employee_id: $record->employee_id,
                start_time: $record->start_time->toDateTimeString(),
                end_time: $record->end_time?->toDateTimeString(),
                talk_time: $record->talk_time,
                hold_time: $record->hold_time,
                work_time: $record->work_time,
                phone_number: $record->phone_number,
                csq_name: $record->csq_name,
                call_type: $record->call_type,
            ));
    }

    public function getStateTransitions(int $employeeId, CarbonInterface $date): Collection
    {
        return AgentStateTransition::where('employee_id', $employeeId)
            ->whereDate('transition_time', $date->toDateString())
            ->orderBy('transition_time')
            ->get()
            ->map(fn (AgentStateTransition $t): AgentStateTransitionDTO => new AgentStateTransitionDTO(
                employee_id: $t->employee_id,
                transition_time: $t->transition_time->toDateTimeString(),
                agent_state: trim((string) $t->agent_state),
                reason_code: $t->reason_code ? trim((string) $t->reason_code) : null,
                duration: $t->duration,
            ));
    }

    public function getBatchStateTransitions(array $employeeIds, CarbonInterface $date): Collection
    {
        return AgentStateTransition::whereIn('employee_id', $employeeIds)
            ->whereDate('transition_time', $date->toDateString())
            ->get()
            ->map(fn (AgentStateTransition $t): AgentStateTransitionDTO => new AgentStateTransitionDTO(
                employee_id: $t->employee_id,
                transition_time: $t->transition_time->toDateTimeString(),
                agent_state: trim((string) $t->agent_state),
                reason_code: $t->reason_code ? trim((string) $t->reason_code) : null,
                duration: $t->duration,
            ));
    }

    public function getDailyMetric(int $employeeId, CarbonInterface $date): ?AgentDailyMetricDTO
    {
        $metric = AgentDailyMetric::where('employee_id', $employeeId)
            ->whereDate('metric_date', $date->toDateString())
            ->first();

        if (! $metric) {
            return null;
        }

        return $this->toDailyMetricDTO($metric);
    }

    public function saveDailyMetric(AgentDailyMetricDTO $metric): AgentDailyMetricDTO
    {
        $model = AgentDailyMetric::updateOrCreate(
            ['employee_id' => $metric->employee_id, 'metric_date' => $metric->metric_date],
            [
                'login_seconds' => $metric->login_seconds,
                'productive_seconds' => $metric->productive_seconds,
                'calls_total' => $metric->calls_total,
                'talk_seconds' => $metric->talk_seconds,
                'weighted_aht' => $metric->weighted_aht,
                'capacity_calls' => $metric->capacity_calls,
                'capacity_gap' => $metric->capacity_gap,
                'work_units' => $metric->work_units,
                'availability_pct' => $metric->availability_pct,
                'efficiency_pct' => $metric->efficiency_pct,
                'pwi_pct' => $metric->pwi_pct,
                'queue_distribution' => $metric->queue_distribution,
            ]
        );

        return $this->toDailyMetricDTO($model);
    }

    public function getCallRecordsInInterval(int $employeeId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return AgentCallPerformance::where('employee_id', $employeeId)
            ->where('start_time', '>=', $start)
            ->where('start_time', '<', $end)
            ->orderBy('start_time')
            ->get()
            ->map(fn (AgentCallPerformance $record): AgentCallRecordDTO => new AgentCallRecordDTO(
                employee_id: $record->employee_id,
                start_time: $record->start_time->toDateTimeString(),
                end_time: $record->end_time?->toDateTimeString(),
                talk_time: $record->talk_time,
                hold_time: $record->hold_time,
                work_time: $record->work_time,
                phone_number: $record->phone_number,
                csq_name: $record->csq_name,
                call_type: $record->call_type,
            ));
    }

    public function getTeamCallRecords(array $teamIds, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return AgentCallPerformance::join('employees', 'agent_call_performances.employee_id', '=', 'employees.id')
            ->whereIn('employees.team_id', $teamIds)
            ->whereBetween('start_time', [$start->toDateString(), $end->toDateString()])
            ->select('agent_call_performances.*')
            ->get()
            ->map(fn (AgentCallPerformance $record): AgentCallRecordDTO => new AgentCallRecordDTO(
                employee_id: $record->employee_id,
                start_time: $record->start_time->toDateTimeString(),
                end_time: $record->end_time?->toDateTimeString(),
                talk_time: $record->talk_time,
                hold_time: $record->hold_time,
                work_time: $record->work_time,
                phone_number: $record->phone_number,
                csq_name: $record->csq_name,
                call_type: $record->call_type,
            ));
    }

    public function getTeamCallStats(array $employeeIds, CarbonInterface $start, CarbonInterface $end): array
    {
        $row = AgentCallPerformance::whereIn('employee_id', $employeeIds)
            ->where('start_time', '>=', $start->toDateString())
            ->where('start_time', '<', $end->copy()->addDay()->toDateString())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(talk_time),0) as talk, COALESCE(SUM(work_time),0) as work')
            ->first();

        return [
            'count' => (int) ($row->count ?? 0),
            'talk' => (int) ($row->talk ?? 0),
            'work' => (int) ($row->work ?? 0),
        ];
    }

    private function toDailyMetricDTO(AgentDailyMetric $metric): AgentDailyMetricDTO
    {
        return new AgentDailyMetricDTO(
            employee_id: $metric->employee_id,
            metric_date: $metric->metric_date->toDateString(),
            login_seconds: $metric->login_seconds,
            productive_seconds: $metric->productive_seconds,
            calls_total: $metric->calls_total,
            talk_seconds: $metric->talk_seconds,
            weighted_aht: (float) $metric->weighted_aht,
            capacity_calls: (float) $metric->capacity_calls,
            capacity_gap: (float) $metric->capacity_gap,
            work_units: (float) $metric->work_units,
            availability_pct: (float) $metric->availability_pct,
            efficiency_pct: (float) $metric->efficiency_pct,
            pwi_pct: (float) $metric->pwi_pct,
            queue_distribution: $metric->queue_distribution ?? [],
        );
    }
}
