<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\DTOs;

/**
 * Datos de entrada validados para crear una dirección.
 */
readonly class DirectorateDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $is_active = true,
    ) {}

    /**
     * Construye el DTO desde un array validado.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
        );
    }
}
