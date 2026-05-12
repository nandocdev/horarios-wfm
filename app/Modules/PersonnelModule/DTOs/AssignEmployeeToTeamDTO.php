<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para asignar un empleado a un equipo.
 */
readonly class AssignEmployeeToTeamDTO
{
    public function __construct(
        public int $employee_id,
        public int $team_id,
        public string $joined_at,
        public ?string $left_at = null,
    ) {}

    /**
     * Construye el DTO desde un array validado (Form Request).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            employee_id: $data['employee_id'],
            team_id: $data['team_id'],
            joined_at: $data['joined_at'] ?? now()->format('Y-m-d'),
            left_at: $data['left_at'] ?? null,
        );
    }
}
