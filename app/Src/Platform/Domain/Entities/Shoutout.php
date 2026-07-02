<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Platform\Domain\ValueObjects\ModerationNotes;
use DateTimeImmutable;

final class Shoutout {
    private function __construct(
        private ?int $id,
        private int $employeeId,
        private string $message,
        private ContentStatus $status,
        private ?int $approvedBy,
        private ?DateTimeImmutable $approvedAt,
        private ?ModerationNotes $moderationNotes,
        private array $versionHistory,
        private bool $isActive,
        private ?DateTimeImmutable $scheduledAt,
        private ?DateTimeImmutable $archiveAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        int $employeeId,
        string $message,
        ?DateTimeImmutable $scheduledAt = null,
        ?DateTimeImmutable $archiveAt = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            employeeId: $employeeId,
            message: $message,
            status: ContentStatus::Draft,
            approvedBy: null,
            approvedAt: null,
            moderationNotes: null,
            versionHistory: [],
            isActive: true,
            scheduledAt: $scheduledAt,
            archiveAt: $archiveAt,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        int $employeeId,
        string $message,
        ContentStatus $status,
        ?int $approvedBy,
        ?DateTimeImmutable $approvedAt,
        ?ModerationNotes $moderationNotes,
        array $versionHistory,
        bool $isActive,
        ?DateTimeImmutable $scheduledAt,
        ?DateTimeImmutable $archiveAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            employeeId: $employeeId,
            message: $message,
            status: $status,
            approvedBy: $approvedBy,
            approvedAt: $approvedAt,
            moderationNotes: $moderationNotes,
            versionHistory: $versionHistory,
            isActive: $isActive,
            scheduledAt: $scheduledAt,
            archiveAt: $archiveAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function employeeId(): int { return $this->employeeId; }
    public function message(): string { return $this->message; }
    public function status(): ContentStatus { return $this->status; }
    public function approvedBy(): ?int { return $this->approvedBy; }
    public function approvedAt(): ?DateTimeImmutable { return $this->approvedAt; }
    public function moderationNotes(): ?ModerationNotes { return $this->moderationNotes; }
    public function versionHistory(): array { return $this->versionHistory; }
    public function isActive(): bool { return $this->isActive; }
    public function scheduledAt(): ?DateTimeImmutable { return $this->scheduledAt; }
    public function archiveAt(): ?DateTimeImmutable { return $this->archiveAt; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
