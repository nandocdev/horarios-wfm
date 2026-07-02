<?php

declare(strict_types=1);

namespace App\Src\Workflows\Domain\Entities;

use DateTimeImmutable;

final class ApprovalSignature
{
    public function __construct(
        private readonly ?int $id,
        private readonly ?int $approvalRequestId,
        private readonly int $approverId,
        private readonly string $action,
        private readonly ?string $comment = null,
        private readonly int $level = 1,
        private readonly ?DateTimeImmutable $signedAt = null,
    ) {}

    public static function approve(int $approverId, ?string $comment = null, int $level = 1): self
    {
        return new self(null, null, $approverId, 'approved', $comment, $level);
    }

    public static function reject(int $approverId, string $reason, int $level = 1): self
    {
        return new self(null, null, $approverId, 'rejected', $reason, $level);
    }

    public function id(): ?int { return $this->id; }
    public function approvalRequestId(): ?int { return $this->approvalRequestId; }
    public function approverId(): int { return $this->approverId; }
    public function action(): string { return $this->action; }
    public function comment(): ?string { return $this->comment; }
    public function level(): int { return $this->level; }
    public function signedAt(): ?DateTimeImmutable { return $this->signedAt; }

    public function isApproved(): bool { return $this->action === 'approved'; }
    public function isRejected(): bool { return $this->action === 'rejected'; }
}
