<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use DateTimeImmutable;

final class Comment {
    private function __construct(
        private ?int $id,
        private int $newsId,
        private int $userId,
        private string $content,
        private ?int $parentId,
        private bool $isActive,
        private ?DateTimeImmutable $publishedAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        int $newsId,
        int $userId,
        string $content,
        ?int $parentId = null,
        ?DateTimeImmutable $publishedAt = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            newsId: $newsId,
            userId: $userId,
            content: $content,
            parentId: $parentId,
            isActive: true,
            publishedAt: $publishedAt ?? $now,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        int $newsId,
        int $userId,
        string $content,
        ?int $parentId,
        bool $isActive,
        ?DateTimeImmutable $publishedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            newsId: $newsId,
            userId: $userId,
            content: $content,
            parentId: $parentId,
            isActive: $isActive,
            publishedAt: $publishedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function newsId(): int { return $this->newsId; }
    public function userId(): int { return $this->userId; }
    public function content(): string { return $this->content; }
    public function parentId(): ?int { return $this->parentId; }
    public function isActive(): bool { return $this->isActive; }
    public function publishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
