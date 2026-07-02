<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use App\Src\Platform\Domain\ValueObjects\ContentStatus;
use App\Src\Platform\Domain\ValueObjects\ModerationNotes;
use DateTimeImmutable;

final class News {
    private function __construct(
        private ?int $id,
        private string $title,
        private string $slug,
        private ?string $excerpt,
        private string $content,
        private int $authorId,
        private ContentStatus $status,
        private ?int $approvedBy,
        private ?DateTimeImmutable $approvedAt,
        private ?ModerationNotes $moderationNotes,
        private array $versionHistory,
        private bool $isActive,
        private ?DateTimeImmutable $publishedAt,
        private ?DateTimeImmutable $scheduledAt,
        private ?DateTimeImmutable $archiveAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        string $title,
        string $slug,
        ?string $excerpt,
        string $content,
        int $authorId,
        ?DateTimeImmutable $publishedAt = null,
        ?DateTimeImmutable $scheduledAt = null,
        ?DateTimeImmutable $archiveAt = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            title: $title,
            slug: $slug,
            excerpt: $excerpt,
            content: $content,
            authorId: $authorId,
            status: ContentStatus::Draft,
            approvedBy: null,
            approvedAt: null,
            moderationNotes: null,
            versionHistory: [],
            isActive: true,
            publishedAt: $publishedAt,
            scheduledAt: $scheduledAt,
            archiveAt: $archiveAt,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        string $title,
        string $slug,
        ?string $excerpt,
        string $content,
        int $authorId,
        ContentStatus $status,
        ?int $approvedBy,
        ?DateTimeImmutable $approvedAt,
        ?ModerationNotes $moderationNotes,
        array $versionHistory,
        bool $isActive,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $scheduledAt,
        ?DateTimeImmutable $archiveAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            title: $title,
            slug: $slug,
            excerpt: $excerpt,
            content: $content,
            authorId: $authorId,
            status: $status,
            approvedBy: $approvedBy,
            approvedAt: $approvedAt,
            moderationNotes: $moderationNotes,
            versionHistory: $versionHistory,
            isActive: $isActive,
            publishedAt: $publishedAt,
            scheduledAt: $scheduledAt,
            archiveAt: $archiveAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function title(): string { return $this->title; }
    public function slug(): string { return $this->slug; }
    public function excerpt(): ?string { return $this->excerpt; }
    public function content(): string { return $this->content; }
    public function authorId(): int { return $this->authorId; }
    public function status(): ContentStatus { return $this->status; }
    public function approvedBy(): ?int { return $this->approvedBy; }
    public function approvedAt(): ?DateTimeImmutable { return $this->approvedAt; }
    public function moderationNotes(): ?ModerationNotes { return $this->moderationNotes; }
    public function versionHistory(): array { return $this->versionHistory; }
    public function isActive(): bool { return $this->isActive; }
    public function publishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function scheduledAt(): ?DateTimeImmutable { return $this->scheduledAt; }
    public function archiveAt(): ?DateTimeImmutable { return $this->archiveAt; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
