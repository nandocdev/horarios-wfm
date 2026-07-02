<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use App\Src\Platform\Domain\ValueObjects\ReactionType;
use DateTimeImmutable;

final class Reaction {
    private function __construct(
        private ?int $id,
        private int $shoutoutId,
        private int $userId,
        private ReactionType $type,
        private bool $isActive,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        int $shoutoutId,
        int $userId,
        ReactionType $type,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            shoutoutId: $shoutoutId,
            userId: $userId,
            type: $type,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        int $shoutoutId,
        int $userId,
        ReactionType $type,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            shoutoutId: $shoutoutId,
            userId: $userId,
            type: $type,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function shoutoutId(): int { return $this->shoutoutId; }
    public function userId(): int { return $this->userId; }
    public function type(): ReactionType { return $this->type; }
    public function isActive(): bool { return $this->isActive; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
