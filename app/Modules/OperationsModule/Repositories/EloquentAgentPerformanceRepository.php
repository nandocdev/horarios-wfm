<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Repositories;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use App\Shared\Contracts\Operations\AgentPerformanceRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class EloquentAgentPerformanceRepository implements AgentPerformanceRepositoryInterface
{
    public function getCallRecords(int $employeeId, CarbonInterface $date): Collection
    {
        return AgentCallPerformance::where('employee_id', $employeeId)
            ->whereDate('start_time', $date->toDateString())
            ->orderBy('start_time')
            ->get();
    }

    public function getStateTransitions(int $employeeId, CarbonInterface $date): Collection
    {
        return AgentStateTransition::where('employee_id', $employeeId)
            ->whereDate('transition_time', $date->toDateString())
            ->orderBy('transition_time')
            ->get();
    }

    public function getBatchStateTransitions(array $employeeIds, CarbonInterface $date): Collection
    {
        return AgentStateTransition::whereIn('employee_id', $employeeIds)
            ->whereDate('transition_time', $date->toDateString())
            ->get();
    }

    public function getDailyMetric(int $employeeId, CarbonInterface $date): ?AgentDailyMetric
    {
        return AgentDailyMetric::where('employee_id', $employeeId)
            ->whereDate('metric_date', $date->toDateString())
            ->first();
    }

    public function saveDailyMetric(AgentDailyMetric $metric): AgentDailyMetric
    {
        $metric->save();

        return $metric;
    }

    public function getTeamCallRecords(array $teamIds, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return AgentCallPerformance::join('employees', 'agent_call_performances.employee_id', '=', 'employees.id')
            ->whereIn('employees.team_id', $teamIds)
            ->whereBetween('start_time', [$start->toDateString(), $end->toDateString()])
            ->select('agent_call_performances.*')
            ->get();
    }
}
