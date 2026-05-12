<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\DTOs;

/**
 * Datos de entrada validados para remover un empleado de un equipo.
 */
readonly class RemoveEmployeeFromTeamDTO
{
    public function __construct(
        public int $employee_id,
        public int $team_id,
        public string $left_at,
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
            left_at: $data['left_at'] ?? $data['end_date'] ?? now()->format('Y-m-d'),
        );
    }
}
