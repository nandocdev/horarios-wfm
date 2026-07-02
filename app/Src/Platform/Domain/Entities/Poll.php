<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Platform\Domain\ValueObjects\ModerationNotes;
use DateTimeImmutable;

final class Poll {
    private function __construct(
        private ?int $id,
        private string $question,
        private array $options,
        private ContentStatus $status,
        private ?int $approvedBy,
        private ?DateTimeImmutable $approvedAt,
        private ?ModerationNotes $moderationNotes,
        private array $versionHistory,
        private bool $isActive,
        private ?DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $scheduledAt,
        private ?DateTimeImmutable $archiveAt,
        private ?DateTimeImmutable $reminderSentAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private array $votes = [],
    ) {
    }

    public static function create(
        string $question,
        array $options,
        ?DateTimeImmutable $expiresAt = null,
        ?DateTimeImmutable $scheduledAt = null,
        ?DateTimeImmutable $archiveAt = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            question: $question,
            options: $options,
            status: ContentStatus::Draft,
            approvedBy: null,
            approvedAt: null,
            moderationNotes: null,
            versionHistory: [],
            isActive: true,
            expiresAt: $expiresAt,
            scheduledAt: $scheduledAt,
            archiveAt: $archiveAt,
            reminderSentAt: null,
            createdAt: $now,
            updatedAt: $now,
            votes: [],
        );
    }

    public static function fromDatabase(
        int $id,
        string $question,
        array $options,
        ContentStatus $status,
        ?int $approvedBy,
        ?DateTimeImmutable $approvedAt,
        ?ModerationNotes $moderationNotes,
        array $versionHistory,
        bool $isActive,
        ?DateTimeImmutable $expiresAt,
        ?DateTimeImmutable $scheduledAt,
        ?DateTimeImmutable $archiveAt,
        ?DateTimeImmutable $reminderSentAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        array $votes = [],
    ): self {
        return new self(
            id: $id,
            question: $question,
            options: $options,
            status: $status,
            approvedBy: $approvedBy,
            approvedAt: $approvedAt,
            moderationNotes: $moderationNotes,
            versionHistory: $versionHistory,
            isActive: $isActive,
            expiresAt: $expiresAt,
            scheduledAt: $scheduledAt,
            archiveAt: $archiveAt,
            reminderSentAt: $reminderSentAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            votes: $votes,
        );
    }

    public function hasVoted(int $userId): bool
    {
        return isset($this->votes[$userId]);
    }

    public function recordVote(int $userId, string $answer): void
    {
        $this->votes[$userId] = $answer;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new DateTimeImmutable();
    }

    public function id(): ?int { return $this->id; }
    public function question(): string { return $this->question; }
    public function options(): array { return $this->options; }
    public function status(): ContentStatus { return $this->status; }
    public function approvedBy(): ?int { return $this->approvedBy; }
    public function approvedAt(): ?DateTimeImmutable { return $this->approvedAt; }
    public function moderationNotes(): ?ModerationNotes { return $this->moderationNotes; }
    public function versionHistory(): array { return $this->versionHistory; }
    public function isActive(): bool { return $this->isActive; }
    public function expiresAt(): ?DateTimeImmutable { return $this->expiresAt; }
    public function scheduledAt(): ?DateTimeImmutable { return $this->scheduledAt; }
    public function archiveAt(): ?DateTimeImmutable { return $this->archiveAt; }
    public function reminderSentAt(): ?DateTimeImmutable { return $this->reminderSentAt; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function votes(): array { return $this->votes; }
}
