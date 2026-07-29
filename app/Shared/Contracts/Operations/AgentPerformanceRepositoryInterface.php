<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Operations;

use App\Shared\DTOs\Operations\AgentCallRecordDTO;
use App\Shared\DTOs\Operations\AgentDailyMetricDTO;
use App\Shared\DTOs\Operations\AgentStateTransitionDTO;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface AgentPerformanceRepositoryInterface
{
    /**
     * @return Collection<int, AgentCallRecordDTO>
     */
    public function getCallRecords(int $employeeId, CarbonInterface $date): Collection;

    /**
     * @return Collection<int, AgentStateTransitionDTO>
     */
    public function getStateTransitions(int $employeeId, CarbonInterface $date): Collection;

    /**
     * @param  int[]  $employeeIds
     * @return Collection<int, AgentStateTransitionDTO>
     */
    public function getBatchStateTransitions(array $employeeIds, CarbonInterface $date): Collection;

    public function getDailyMetric(int $employeeId, CarbonInterface $date): ?AgentDailyMetricDTO;

    public function saveDailyMetric(AgentDailyMetricDTO $metric): AgentDailyMetricDTO;

    /**
     * @return Collection<int, AgentCallRecordDTO>
     */
    public function getTeamCallRecords(array $teamIds, CarbonInterface $start, CarbonInterface $end): Collection;

    /**
     * @return Collection<int, AgentCallRecordDTO>
     */
    public function getCallRecordsInInterval(int $employeeId, CarbonInterface $start, CarbonInterface $end): Collection;
}
