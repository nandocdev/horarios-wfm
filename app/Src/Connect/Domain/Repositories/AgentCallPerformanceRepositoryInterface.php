<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\AgentCallPerformance;

interface AgentCallPerformanceRepositoryInterface
{
    public function save(AgentCallPerformance $performance): AgentCallPerformance;
    public function upsert(AgentCallPerformance $performance): AgentCallPerformance;
    public function findById(int $id): ?AgentCallPerformance;
    public function findByEmployee(int $employeeId, string $date): array;
    public function findByDateRange(string $dateFrom, string $dateTo): array;
}
