<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use DateTimeImmutable;

final class CsqRealtimeStat
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?string $csqName,
        private readonly ?int $callsWaiting,
        private readonly ?int $longestCallInQueue,
        private readonly ?int $agentsLoggedOn,
        private readonly ?int $agentsTalking,
        private readonly ?int $agentsReady,
        private readonly ?int $agentsNotReady,
        private readonly ?int $agentsAfterCallWork,
        private readonly ?int $agentsReserved,
        private readonly ?float $serviceLevelShortTerm,
        private readonly ?float $serviceLevelLongTerm,
        private readonly ?int $callsAbandonedSinceMidnight,
        private readonly ?int $callsHandledSinceMidnight,
        private readonly ?int $totalCallsSinceMidnight,
        private readonly ?array $metadata,
        private readonly ?DateTimeImmutable $createdAt,
    ) {}

    public function id(): ?int { return $this->id; }
    public function csqName(): ?string { return $this->csqName; }
    public function callsWaiting(): ?int { return $this->callsWaiting; }
    public function longestCallInQueue(): ?int { return $this->longestCallInQueue; }
    public function agentsLoggedOn(): ?int { return $this->agentsLoggedOn; }
    public function agentsTalking(): ?int { return $this->agentsTalking; }
    public function agentsReady(): ?int { return $this->agentsReady; }
    public function agentsNotReady(): ?int { return $this->agentsNotReady; }
    public function agentsAfterCallWork(): ?int { return $this->agentsAfterCallWork; }
    public function agentsReserved(): ?int { return $this->agentsReserved; }
    public function serviceLevelShortTerm(): ?float { return $this->serviceLevelShortTerm; }
    public function serviceLevelLongTerm(): ?float { return $this->serviceLevelLongTerm; }
    public function callsAbandonedSinceMidnight(): ?int { return $this->callsAbandonedSinceMidnight; }
    public function callsHandledSinceMidnight(): ?int { return $this->callsHandledSinceMidnight; }
    public function totalCallsSinceMidnight(): ?int { return $this->totalCallsSinceMidnight; }
    public function metadata(): ?array { return $this->metadata; }
    public function createdAt(): ?DateTimeImmutable { return $this->createdAt; }
}
