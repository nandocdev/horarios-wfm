<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use DateTimeImmutable;

final class CallRecord
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?string $ciscoCallId,
        private readonly ?int $queueId,
        private readonly ?string $phoneNumber,
        private readonly ?string $citizenIdentifier,
        private readonly ?int $employeeId,
        private readonly ?string $rawAgentName,
        private readonly ?int $caseSubtypeId,
        private readonly ?string $description,
        private readonly ?string $status,
        private readonly ?int $talkTime,
        private readonly ?int $ringTime,
        private readonly ?int $workTime,
        private readonly ?int $queueTime,
        private readonly ?int $contactDisposition,
        private readonly ?DateTimeImmutable $ivrStartedAt,
        private readonly ?DateTimeImmutable $ivrEndedAt,
        private readonly ?DateTimeImmutable $closedAt,
    ) {}

    public function id(): ?int { return $this->id; }
    public function ciscoCallId(): ?string { return $this->ciscoCallId; }
    public function queueId(): ?int { return $this->queueId; }
    public function phoneNumber(): ?string { return $this->phoneNumber; }
    public function citizenIdentifier(): ?string { return $this->citizenIdentifier; }
    public function employeeId(): ?int { return $this->employeeId; }
    public function rawAgentName(): ?string { return $this->rawAgentName; }
    public function caseSubtypeId(): ?int { return $this->caseSubtypeId; }
    public function description(): ?string { return $this->description; }
    public function status(): ?string { return $this->status; }
    public function talkTime(): ?int { return $this->talkTime; }
    public function ringTime(): ?int { return $this->ringTime; }
    public function workTime(): ?int { return $this->workTime; }
    public function queueTime(): ?int { return $this->queueTime; }
    public function contactDisposition(): ?int { return $this->contactDisposition; }
    public function ivrStartedAt(): ?DateTimeImmutable { return $this->ivrStartedAt; }
    public function ivrEndedAt(): ?DateTimeImmutable { return $this->ivrEndedAt; }
    public function closedAt(): ?DateTimeImmutable { return $this->closedAt; }
}
