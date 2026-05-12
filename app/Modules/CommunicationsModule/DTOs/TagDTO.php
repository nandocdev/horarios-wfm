<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\DTOs;

/**
 * Datos de entrada validados para crear o actualizar un tag.
 */
readonly class TagDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $color = '#6B7280',
        public bool $is_active = true,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? str($data['name'])->slug()->toString(),
            color: $data['color'] ?? '#6B7280',
            is_active: $data['is_active'] ?? true,
        );
    }
}
