<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * Datos de entrada validados para crear o actualizar una categoría.
 */
readonly class CategoryDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $description = null,
        public string $color = '#3B82F6',
        public bool $is_active = true,
        public int $sort_order = 0,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? str($data['name'])->slug()->toString(),
            description: $data['description'] ?? null,
            color: $data['color'] ?? '#3B82F6',
            is_active: $data['is_active'] ?? true,
            sort_order: $data['sort_order'] ?? 0,
        );
    }
}
