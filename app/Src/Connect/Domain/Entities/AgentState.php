<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use DateTimeImmutable;

final class AgentState
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $employeeId,
        private readonly string $externalId,
        private readonly string $currentState,
        private readonly ?string $reasonCode,
        private readonly DateTimeImmutable $lastChangedAt,
        private readonly TelephonyProvider $provider,
        private readonly ?array $metadata = null,
    ) {}

    public static function fromTelemetry(
        int $employeeId,
        string $externalId,
        string $currentState,
        ?string $reasonCode,
        DateTimeImmutable $lastChangedAt,
        ?TelephonyProvider $provider = null,
    ): self {
        return new self(null, $employeeId, $externalId, $currentState, $reasonCode, $lastChangedAt, $provider ?? TelephonyProvider::ciscoFinesse());
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function externalId(): string { return $this->externalId; }
    public function currentState(): string { return $this->currentState; }
    public function reasonCode(): ?string { return $this->reasonCode; }
    public function lastChangedAt(): DateTimeImmutable { return $this->lastChangedAt; }
    public function provider(): TelephonyProvider { return $this->provider; }
    public function metadata(): ?array { return $this->metadata; }
}
