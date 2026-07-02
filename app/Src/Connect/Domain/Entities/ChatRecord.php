<?php

declare(strict_types=1);

namespace App\Src\Connect\Domain\Entities;

use DateTimeImmutable;

final class ChatRecord
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?string $conversationId,
        private readonly ?string $agentLoginId,
        private readonly ?int $employeeId,
        private readonly ?DateTimeImmutable $startTime,
        private readonly ?DateTimeImmutable $endTime,
        private readonly ?DateTimeImmutable $acceptedAt,
        private readonly ?int $totalDuration,
        private readonly ?int $talkTime,
        private readonly ?string $authorIdentifier,
        private readonly ?string $destinationIdentifier,
        private readonly ?string $chatType,
        private readonly ?string $chatSource,
        private readonly ?int $chatRating,
        private readonly ?string $rawAgentName,
    ) {}

    public function id(): ?int { return $this->id; }
    public function conversationId(): ?string { return $this->conversationId; }
    public function agentLoginId(): ?string { return $this->agentLoginId; }
    public function employeeId(): ?int { return $this->employeeId; }
    public function startTime(): ?DateTimeImmutable { return $this->startTime; }
    public function endTime(): ?DateTimeImmutable { return $this->endTime; }
    public function acceptedAt(): ?DateTimeImmutable { return $this->acceptedAt; }
    public function totalDuration(): ?int { return $this->totalDuration; }
    public function talkTime(): ?int { return $this->talkTime; }
    public function authorIdentifier(): ?string { return $this->authorIdentifier; }
    public function destinationIdentifier(): ?string { return $this->destinationIdentifier; }
    public function chatType(): ?string { return $this->chatType; }
    public function chatSource(): ?string { return $this->chatSource; }
    public function chatRating(): ?int { return $this->chatRating; }
    public function rawAgentName(): ?string { return $this->rawAgentName; }
}
