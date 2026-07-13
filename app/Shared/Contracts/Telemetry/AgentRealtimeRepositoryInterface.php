<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Telemetry;

use Illuminate\Support\Collection;

interface AgentRealtimeRepositoryInterface
{
    public function getRealtimeStates(?array $employeeIds = null): Collection;

    public function getLatestUpdate(): ?string;

    /**
     * @return Collection<int, string>
     */
    public function getDistinctReasonCodes(): Collection;

    public function getAgentHistory(int $employeeId, string $date, int $limit = 10): Collection;

    /**
     * @return Collection<string, int>
     */
    public function getQueueAhtGoals(): Collection;

    /**
     * @return Collection<int, array>
     */
    public function getAllQueues(): Collection;

    /**
     * @return Collection<int, array>
     */
    public function getQueueStats(int $limit = 6): Collection;

    /**
     * @return Collection<int, string>
     */
    public function getCallTrends(string $from, string $to): Collection;

    /**
     * @return array<string, int>
     */
    public function getStateDistribution(?array $employeeIds = null): array;

    /**
     * @return Collection<int, object>
     */
    public function getBatchStateTransitions(array $employeeIds, string $date): Collection;

    /**
     * @return object{total: int, handled: int}
     */
    public function getCallStatsForDate(string $date): object;

    public function getAverageServiceLevel(): float;
}
