<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\DTOs;

final readonly class TagDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $color = '#6B7280',
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? str($data['name'])->slug()->toString(),
            color: $data['color'] ?? '#6B7280',
            isActive: $data['is_active'] ?? true,
        );
    }
}
