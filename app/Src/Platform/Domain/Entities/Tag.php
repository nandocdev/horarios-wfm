<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use DateTimeImmutable;

final class Tag {
    private function __construct(
        private ?int $id,
        private string $name,
        private string $slug,
        private ?string $color,
        private bool $isActive,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        string $name,
        string $slug,
        ?string $color = null,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            name: $name,
            slug: $slug,
            color: $color,
            isActive: true,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        string $name,
        string $slug,
        ?string $color,
        bool $isActive,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            color: $color,
            isActive: $isActive,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function slug(): string { return $this->slug; }
    public function color(): ?string { return $this->color; }
    public function isActive(): bool { return $this->isActive; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
