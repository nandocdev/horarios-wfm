<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class CategoryDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public string $color = '#3B82F6',
        public bool $isActive = true,
        public int $sortOrder = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? str($data['name'])->slug()->toString(),
            description: $data['description'] ?? null,
            color: $data['color'] ?? '#3B82F6',
            isActive: $data['is_active'] ?? true,
            sortOrder: (int) ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
        );
    }
}
