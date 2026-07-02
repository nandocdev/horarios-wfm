<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\AgentStateTransition;

interface AgentStateTransitionRepositoryInterface
{
    public function save(AgentStateTransition $transition): AgentStateTransition;
    public function bulkInsert(array $transitions): void;
    public function findByEmployee(int $employeeId, string $dateFrom, string $dateTo): array;
    public function findLatestByEmployee(int $employeeId): ?AgentStateTransition;
}
