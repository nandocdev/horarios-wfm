<?php

declare(strict_types=1);

namespace App\Src\Analytics\Domain\Repositories;

use App\Src\Analytics\Domain\Entities\AgentDailyMetric;
use DateTimeImmutable;

interface AnalyticsRepositoryInterface {
    public function saveMetric(AgentDailyMetric $metric): AgentDailyMetric;
    public function findMetricByEmployeeAndDate(int $employeeId, DateTimeImmutable $date): ?AgentDailyMetric;
    public function aggregateByTeam(int $teamId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;
    public function getLatestMetricsByEmployee(array $employeeIds): array;
}
