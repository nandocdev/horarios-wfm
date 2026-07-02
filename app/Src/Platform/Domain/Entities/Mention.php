<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use DateTimeImmutable;

final class Mention {
    private function __construct(
        private ?int $id,
        private int $mentionedUserId,
        private int $mentionerUserId,
        private string $mentionableType,
        private int $mentionableId,
        private ?string $context,
        private bool $isRead,
        private ?DateTimeImmutable $readAt,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        int $mentionedUserId,
        int $mentionerUserId,
        string $mentionableType,
        int $mentionableId,
        ?string $context = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            mentionedUserId: $mentionedUserId,
            mentionerUserId: $mentionerUserId,
            mentionableType: $mentionableType,
            mentionableId: $mentionableId,
            context: $context,
            isRead: false,
            readAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        int $mentionedUserId,
        int $mentionerUserId,
        string $mentionableType,
        int $mentionableId,
        ?string $context,
        bool $isRead,
        ?DateTimeImmutable $readAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            mentionedUserId: $mentionedUserId,
            mentionerUserId: $mentionerUserId,
            mentionableType: $mentionableType,
            mentionableId: $mentionableId,
            context: $context,
            isRead: $isRead,
            readAt: $readAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function mentionedUserId(): int { return $this->mentionedUserId; }
    public function mentionerUserId(): int { return $this->mentionerUserId; }
    public function mentionableType(): string { return $this->mentionableType; }
    public function mentionableId(): int { return $this->mentionableId; }
    public function context(): ?string { return $this->context; }
    public function isRead(): bool { return $this->isRead; }
    public function readAt(): ?DateTimeImmutable { return $this->readAt; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
