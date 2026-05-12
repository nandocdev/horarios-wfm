<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\DTOs\RemoveEmployeeFromTeamDTO;
use App\Modules\PersonnelModule\Models\TeamMember;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Remueve un empleado de un equipo desactivando su asignación activa.
 *
 * @throws QueryException
 */
class RemoveEmployeeFromTeamAction
{
    /**
     * Ejecuta la remoción del empleado del equipo.
     *
     * @param  RemoveEmployeeFromTeamDTO  $dto  Datos validados de la remoción
     * @return TeamMember Registro de asignación actualizado
     */
    public function execute(RemoveEmployeeFromTeamDTO $dto): TeamMember
    {
        return DB::transaction(function () use ($dto) {
            /** @var TeamMember $teamMember */
            $teamMember = TeamMember::where('team_id', $dto->team_id)
                ->where('employee_id', $dto->employee_id)
                ->where('is_active', true)
                ->firstOrFail();

            $teamMember->update([
                'is_active' => false,
                'left_at' => $dto->left_at,
            ]);

            // Limpiar la jerarquía del empleado
            Employee::where('id', $dto->employee_id)->update([
                'team_id' => null,
                'parent_id' => null,
                'updated_at' => now(),
            ]);

            return $teamMember;
        });
    }
}
