<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\DTOs;

/**
 * Datos de entrada validados para crear un departamento.
 */
readonly class DepartmentDTO
{
    public function __construct(
        public int $directorate_id,
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
            directorate_id: $data['directorate_id'],
            name: $data['name'],
            description: $data['description'] ?? null,
        );
    }
}
