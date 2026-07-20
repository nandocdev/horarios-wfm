<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para crear un equipo.
 */
readonly class TeamDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $supervisor_id = null,
        public ?string $cisco_team_id = null,
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
            supervisor_id: $data['supervisor_id'] ?? null,
            cisco_team_id: $data['cisco_team_id'] ?? null,
            is_active: $data['is_active'] ?? true,
        );
    }
}
