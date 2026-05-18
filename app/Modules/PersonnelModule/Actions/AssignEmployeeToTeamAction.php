<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\DTOs\AssignEmployeeToTeamDTO;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\PersonnelModule\Models\TeamMember;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Asigna un empleado a un equipo creando un registro en team_members.
 *
 * @throws QueryException
 */
class AssignEmployeeToTeamAction
{
    /**
     * Ejecuta la asignación del empleado al equipo.
     *
     * @param  AssignEmployeeToTeamDTO  $dto  Datos validados de la asignación
     * @return TeamMember Registro de asignación creado
     */
    public function execute(AssignEmployeeToTeamDTO $dto): TeamMember
    {
        return DB::transaction(function () use ($dto) {
            // Desactivar CUALQUIER asignación activa previa del empleado (incluyendo el mismo equipo)
            // para garantizar que solo exista una membresía activa a la vez.
            TeamMember::where('employee_id', $dto->employee_id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'left_at' => $dto->joined_at,
                    'updated_at' => now(),
                ]);

            // Obtener el supervisor del equipo para sincronizar el parent_id (jerarquía)
            $team = Team::find($dto->team_id);
            $supervisorId = $team?->supervisor_id;

            // Sincronizar el registro del empleado (denormalización y jerarquía)
            Employee::where('id', $dto->employee_id)->update([
                'team_id' => $dto->team_id,
                'parent_id' => ($supervisorId && (int) $dto->employee_id !== (int) $supervisorId) ? $supervisorId : null,
                'updated_at' => now(),
            ]);

            // Crear o actualizar la asignación al equipo destino
            return TeamMember::updateOrCreate(
                [
                    'team_id' => $dto->team_id,
                    'employee_id' => $dto->employee_id,
                    'joined_at' => $dto->joined_at,
                ],
                [
                    'left_at' => $dto->left_at,
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        });
    }
}
