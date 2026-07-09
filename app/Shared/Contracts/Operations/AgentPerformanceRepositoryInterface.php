<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Operations;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Modules\OperationsModule\Models\AgentDailyMetric;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface AgentPerformanceRepositoryInterface
{
    /**
     * @return Collection<int, AgentCallPerformance>
     */
    public function getCallRecords(int $employeeId, CarbonInterface $date): Collection;

    /**
     * @return Collection<int, AgentStateTransition>
     */
    public function getStateTransitions(int $employeeId, CarbonInterface $date): Collection;

    /**
     * @param  int[]  $employeeIds
     * @return Collection<int, AgentStateTransition>
     */
    public function getBatchStateTransitions(array $employeeIds, CarbonInterface $date): Collection;

    public function getDailyMetric(int $employeeId, CarbonInterface $date): ?AgentDailyMetric;

    public function saveDailyMetric(AgentDailyMetric $metric): AgentDailyMetric;

    /**
     * @return Collection<int, AgentCallPerformance>
     */
    public function getTeamCallRecords(array $teamIds, CarbonInterface $start, CarbonInterface $end): Collection;
}
