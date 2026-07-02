<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use App\Src\Connect\Domain\ValueObjects\CallStatus;
use App\Src\Connect\Domain\ValueObjects\TelephonyProvider;
use DateTimeImmutable;

final class CallEvent
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $externalCallId,
        private readonly TelephonyProvider $provider,
        private readonly string $type,
        private readonly CallStatus $status,
        private readonly ?string $queueName,
        private readonly ?string $phoneNumber,
        private readonly ?string $citizenIdentifier,
        private readonly ?int $employeeId,
        private readonly ?string $agentLoginId,
        private readonly ?DateTimeImmutable $startedAt,
        private readonly ?DateTimeImmutable $endedAt,
        private readonly ?int $talkTime,
        private readonly ?array $metadata,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromCiscoWebhook(
        string $externalCallId,
        string $type,
        CallStatus $status,
        ?string $queueName = null,
        ?string $phoneNumber = null,
        ?string $agentLoginId = null,
        ?DateTimeImmutable $startedAt = null,
        ?DateTimeImmutable $endedAt = null,
        ?int $talkTime = null,
        ?array $metadata = null,
    ): self {
        return new self(
            null, $externalCallId, TelephonyProvider::ciscoFinesse(),
            $type, $status, $queueName, $phoneNumber, null, null,
            $agentLoginId, $startedAt, $endedAt, $talkTime, $metadata,
            new DateTimeImmutable(),
        );
    }

    public static function fromAvayaWebhook(
        string $externalCallId,
        string $type,
        CallStatus $status,
        ?string $queueName = null,
        ?string $phoneNumber = null,
        ?string $agentLoginId = null,
    ): self {
        return new self(
            null, $externalCallId, TelephonyProvider::avaya(),
            $type, $status, $queueName, $phoneNumber, null, null,
            $agentLoginId, null, null, null, null,
            new DateTimeImmutable(),
        );
    }

    public function id(): ?int { return $this->id; }
    public function externalCallId(): string { return $this->externalCallId; }
    public function provider(): TelephonyProvider { return $this->provider; }
    public function type(): string { return $this->type; }
    public function status(): CallStatus { return $this->status; }
    public function queueName(): ?string { return $this->queueName; }
    public function phoneNumber(): ?string { return $this->phoneNumber; }
    public function citizenIdentifier(): ?string { return $this->citizenIdentifier; }
    public function employeeId(): ?int { return $this->employeeId; }
    public function agentLoginId(): ?string { return $this->agentLoginId; }
    public function startedAt(): ?DateTimeImmutable { return $this->startedAt; }
    public function endedAt(): ?DateTimeImmutable { return $this->endedAt; }
    public function talkTime(): ?int { return $this->talkTime; }
    public function metadata(): ?array { return $this->metadata; }
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
