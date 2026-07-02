<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use DateTimeImmutable;

final class AgentCallPerformance
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?string $agentLoginId,
        private readonly ?int $employeeId,
        private readonly ?string $agentExt,
        private readonly ?DateTimeImmutable $startTime,
        private readonly ?DateTimeImmutable $endTime,
        private readonly ?int $totalDuration,
        private readonly ?int $talkTime,
        private readonly ?int $holdTime,
        private readonly ?int $workTime,
        private readonly ?string $phoneNumber,
        private readonly ?string $ani,
        private readonly ?string $csqName,
        private readonly ?string $callSkill,
        private readonly ?string $callType,
        private readonly ?string $rawAgentName,
    ) {}

    public function id(): ?int { return $this->id; }
    public function agentLoginId(): ?string { return $this->agentLoginId; }
    public function employeeId(): ?int { return $this->employeeId; }
    public function agentExt(): ?string { return $this->agentExt; }
    public function startTime(): ?DateTimeImmutable { return $this->startTime; }
    public function endTime(): ?DateTimeImmutable { return $this->endTime; }
    public function totalDuration(): ?int { return $this->totalDuration; }
    public function talkTime(): ?int { return $this->talkTime; }
    public function holdTime(): ?int { return $this->holdTime; }
    public function workTime(): ?int { return $this->workTime; }
    public function phoneNumber(): ?string { return $this->phoneNumber; }
    public function ani(): ?string { return $this->ani; }
    public function csqName(): ?string { return $this->csqName; }
    public function callSkill(): ?string { return $this->callSkill; }
    public function callType(): ?string { return $this->callType; }
    public function rawAgentName(): ?string { return $this->rawAgentName; }
}
