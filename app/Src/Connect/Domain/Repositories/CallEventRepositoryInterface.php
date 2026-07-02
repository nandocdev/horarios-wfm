<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Repositories;

use App\Src\Connect\Domain\Entities\AgentState;
use App\Src\Connect\Domain\Entities\CallEvent;
use DateTimeImmutable;

interface CallEventRepositoryInterface
{
    public function saveCallEvent(CallEvent $event): CallEvent;
    public function findByExternalId(string $externalCallId, string $provider): ?CallEvent;
    public function findOpenByEmployee(int $employeeId): array;
    public function findCallsByDate(DateTimeImmutable $date, ?int $queueId = null): array;

    public function saveAgentState(AgentState $state): AgentState;
    public function findAgentState(int $employeeId): ?AgentState;
}
