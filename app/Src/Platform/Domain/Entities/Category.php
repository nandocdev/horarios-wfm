<?php

declare(strict_types=1);

namespace App\Src\Platform\Domain\Entities;

use DateTimeImmutable;

final class Category {
    private function __construct(
        private ?int $id,
        private string $name,
        private string $slug,
        private ?string $description,
        private ?string $color,
        private bool $isActive,
        private int $sortOrder,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
    }

    public static function create(
        string $name,
        string $slug,
        ?string $description = null,
        ?string $color = null,
        int $sortOrder = 0,
    ): self {
        $now = new DateTimeImmutable();
        return new self(
            id: null,
            name: $name,
            slug: $slug,
            description: $description,
            color: $color,
            isActive: true,
            sortOrder: $sortOrder,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromDatabase(
        int $id,
        string $name,
        string $slug,
        ?string $description,
        ?string $color,
        bool $isActive,
        int $sortOrder,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            color: $color,
            isActive: $isActive,
            sortOrder: $sortOrder,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function id(): ?int { return $this->id; }
    public function name(): string { return $this->name; }
    public function slug(): string { return $this->slug; }
    public function description(): ?string { return $this->description; }
    public function color(): ?string { return $this->color; }
    public function isActive(): bool { return $this->isActive; }
    public function sortOrder(): int { return $this->sortOrder; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): DateTimeImmutable { return $this->updatedAt; }
}
