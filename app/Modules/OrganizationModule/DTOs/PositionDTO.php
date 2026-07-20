<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\DTOs;

/**
 * Datos de entrada validados para crear un cargo.
 */
readonly class PositionDTO
{
    public function __construct(
        public int $department_id,
        public string $name,
        public string $position_code,
        public ?string $description = null,
        public bool $is_active = true,
        public null|int|float|string $salary = null,
    ) {}

    /**
     * Construye el DTO desde un array validado.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            department_id: (int) $data['department_id'],
            name: $data['name'],
            position_code: $data['position_code'],
            description: $data['description'] ?? null,
            is_active: (bool) ($data['is_active'] ?? true),
            salary: $data['salary'] ?? null,
        );
    }
}
