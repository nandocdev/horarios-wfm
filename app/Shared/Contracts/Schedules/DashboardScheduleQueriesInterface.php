<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Schedules;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface DashboardScheduleQueriesInterface
{
    public function getCurrentWeek(string $today): ?object;

    public function getScheduledCount(?array $employeeIds, string $today, int $dayOfWeek): int;

    public function getExceptionCount(?array $employeeIds, string $today): int;

    /**
     * @return array{approved: int, pending: int}
     */
    public function getLeaveCounts(?array $employeeIds, string $today): array;

    public function getActiveIntradayCount(?array $employeeIds, string $nowIso): int;

    public function getTodayIntradayCount(?array $employeeIds, string $today): int;

    /**
     * @return Collection<int, array>
     */
    public function getUpcomingEvents(?array $employeeIds, string $today, int $limit = 6): Collection;

    public function getPendingSwapCount(?array $employeeIds): int;

    /**
     * @return Collection<int, array{hour: string, assigned: int}>
     */
    public function getCoverageSlots(?array $employeeIds, string $today): Collection;

    /**
     * @return array<string, string>
     */
    public function getOperationalSettings(): array;

    /**
     * @return Collection<int, string>
     */
    public function getAbsenceTrends(string $from, string $to): Collection;

    /**
     * @return Collection<int, object>
     */
    public function getExceptionsForRange(array $employeeIds, CarbonInterface $start, CarbonInterface $end): Collection;

    /**
     * @return Collection<int, object>
     */
    public function getOverlappingIntradayActivities(array $employeeIds, CarbonInterface $start, CarbonInterface $end): Collection;

    /**
     * @return Collection<int, object>
     */
    public function getScheduledAssignmentsWithSchedule(array $employeeIds, string $today, int $dayOfWeek): Collection;

    /**
     * @return Collection<int, object>
     */
    public function getScheduledForTime(array $employeeIds, string $today, int $dayOfWeek, string $time): Collection;

    /**
     * @return array<int>
     */
    public function getActiveExceptionIds(array $employeeIds, CarbonInterface $time): array;

    public function getScheduledCountForDay(array $employeeIds, string $today, int $dayOfWeek): int;
}
