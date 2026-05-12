<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para crear un cargo.
 */
readonly class PositionDTO
{
    public function __construct(
        public int $department_id,
        public string $name,
        public ?string $description = null,
    ) {}

    /**
     * Construye el DTO desde un array validado.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            department_id: $data['department_id'],
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }
}
