<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use DateTimeImmutable;

final class AgentStateTransition
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?string $agentLoginId,
        private readonly ?int $employeeId,
        private readonly ?DateTimeImmutable $transitionTime,
        private readonly ?string $agentState,
        private readonly ?string $reasonCode,
        private readonly ?int $duration,
    ) {}

    public function id(): ?int { return $this->id; }
    public function agentLoginId(): ?string { return $this->agentLoginId; }
    public function employeeId(): ?int { return $this->employeeId; }
    public function transitionTime(): ?DateTimeImmutable { return $this->transitionTime; }
    public function agentState(): ?string { return $this->agentState; }
    public function reasonCode(): ?string { return $this->reasonCode; }
    public function duration(): ?int { return $this->duration; }
}
