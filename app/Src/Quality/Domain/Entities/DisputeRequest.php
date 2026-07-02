<?php

declare(strict_types=1);

namespace App\Src\Quality\Domain\Entities;

use DateTimeImmutable;

final class DisputeRequest {
    public const STATUS_OPEN = 'open';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public function __construct(
        private ?int $id,
        private readonly int $evaluationId,
        private readonly int $raisedByAgentId,
        private readonly string $reason,
        private string $status,
        private ?string $resolutionComment,
        private ?int $resolvedByUserId,
        private ?DateTimeImmutable $resolvedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function raise(int $evaluationId, int $agentId, string $reason): self {
        return new self(null, $evaluationId, $agentId, $reason, self::STATUS_OPEN, null, null, null, new DateTimeImmutable());
    }

    public function id(): ?int {
        return $this->id;
    }
    public function evaluationId(): int {
        return $this->evaluationId;
    }
    public function raisedByAgentId(): int {
        return $this->raisedByAgentId;
    }
    public function reason(): string {
        return $this->reason;
    }
    public function status(): string {
        return $this->status;
    }

    public function accept(int $userId, string $comment): void {
        $this->status = self::STATUS_ACCEPTED;
        $this->resolutionComment = $comment;
        $this->resolvedByUserId = $userId;
        $this->resolvedAt = new DateTimeImmutable();
    }

    public function reject(int $userId, string $comment): void {
        $this->status = self::STATUS_REJECTED;
        $this->resolutionComment = $comment;
        $this->resolvedByUserId = $userId;
        $this->resolvedAt = new DateTimeImmutable();
    }
}
