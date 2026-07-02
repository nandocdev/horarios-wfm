<?php

declare(strict_types=1);

namespace App\Src\Workflows\Domain\Entities;

use App\Src\Workflows\Domain\ValueObjects\WorkflowState;
use DateTimeImmutable;

final class ApprovalRequest
{
    private ?int $id;
    private string $type;
    private int $requesterId;
    private array $payload;
    private WorkflowState $state;
    private ?string $reason;
    private ?string $rejectionReason;
    private int $requiredLevels;
    private array $signatures;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public const TYPE_LEAVE = 'leave';
    public const TYPE_SHIFT_SWAP = 'shift_swap';

    public function __construct(
        ?int $id,
        string $type,
        int $requesterId,
        array $payload = [],
        ?WorkflowState $state = null,
        ?string $reason = null,
        ?string $rejectionReason = null,
        int $requiredLevels = 1,
        array $signatures = [],
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->requesterId = $requesterId;
        $this->payload = $payload;
        $this->state = $state ?? new WorkflowState(WorkflowState::PENDING);
        $this->reason = $reason;
        $this->rejectionReason = $rejectionReason;
        $this->requiredLevels = min(max($requiredLevels, 1), 3);
        $this->signatures = $signatures;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public static function submit(string $type, int $requesterId, array $payload = [], ?string $reason = null, int $requiredLevels = 1): self
    {
        return new self(null, $type, $requesterId, $payload, new WorkflowState(WorkflowState::PENDING), $reason, null, $requiredLevels);
    }

    public function id(): ?int { return $this->id; }
    public function type(): string { return $this->type; }
    public function requesterId(): int { return $this->requesterId; }
    public function payload(): array { return $this->payload; }
    public function state(): WorkflowState { return $this->state; }
    public function reason(): ?string { return $this->reason; }
    public function rejectionReason(): ?string { return $this->rejectionReason; }
    public function requiredLevels(): int { return $this->requiredLevels; }
    public function signatures(): array { return $this->signatures; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function currentLevel(): int { return $this->state->level(); }
    public function isPending(): bool { return $this->state->isPending(); }
    public function isApproved(): bool { return $this->state->isApproved(); }

    public function approve(int $approverId, ?string $comment = null): void
    {
        $nextState = match ($this->state->value()) {
            WorkflowState::PENDING => $this->requiredLevels >= 2 ? WorkflowState::L1_APPROVED : WorkflowState::APPROVED,
            WorkflowState::L1_APPROVED => $this->requiredLevels >= 3 ? WorkflowState::L2_APPROVED : WorkflowState::APPROVED,
            WorkflowState::L2_APPROVED => WorkflowState::L3_APPROVED,
            WorkflowState::L3_APPROVED => WorkflowState::APPROVED,
            default => throw new \DomainException("Cannot approve in state '{$this->state}'."),
        };

        $this->state = $this->state->transitionTo(new WorkflowState($nextState));

        $this->signatures[] = new ApprovalSignature(
            null, $this->id, $approverId, 'approved', $comment, $this->state->level(),
        );
    }

    public function reject(int $approverId, string $reason): void
    {
        if ($this->state->isFinal()) {
            throw new \DomainException("Cannot reject a request in final state '{$this->state}'.");
        }

        $this->state = $this->state->transitionTo(new WorkflowState(WorkflowState::REJECTED));
        $this->rejectionReason = $reason;

        $this->signatures[] = new ApprovalSignature(
            null, $this->id, $approverId, 'rejected', $reason, $this->state->level(),
        );
    }

    public function cancel(): void
    {
        if ($this->state->isFinal()) {
            throw new \DomainException("Cannot cancel a request in final state.");
        }
        $this->state = $this->state->transitionTo(new WorkflowState(WorkflowState::CANCELLED));
    }
}
