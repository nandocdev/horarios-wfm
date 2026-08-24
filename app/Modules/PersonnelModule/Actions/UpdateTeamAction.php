<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\DTOs\TeamDTO;
use App\Modules\PersonnelModule\Events\TeamUpdated;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza un equipo existente en el sistema.
 */
class UpdateTeamAction
{
    /**
     * Ejecuta la actualización del equipo.
     *
     * @param  Team  $team  Equipo a actualizar
     * @param  TeamDTO  $dto  Datos validados del equipo
     * @return Team Equipo actualizado y persistido
     */
    public function execute(Team $team, TeamDTO $dto): Team
    {
        return DB::transaction(function () use ($team, $dto) {
            $oldSupervisorId = $team->supervisor_id;

            $team->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'supervisor_id' => $dto->supervisor_id,
                'cisco_team_id' => $dto->cisco_team_id,
                'is_active' => $dto->is_active,
            ]);

            // Si el supervisor cambió, actualizamos la jerarquía de todos los miembros activos excepto el supervisor mismo.
            // supervisor_id es users.id; parent_id es employees.id -> se resuelve el empleado del usuario.
            if ($oldSupervisorId !== $dto->supervisor_id) {
                $supervisorEmployeeId = $dto->supervisor_id
                    ? (int) (Employee::where('user_id', $dto->supervisor_id)->value('id') ?? 0)
                    : 0;

                Employee::whereHas('currentTeamMember', function ($q) use ($team) {
                    $q->where('team_id', $team->id);
                })
                    ->when($supervisorEmployeeId > 0, fn ($q) => $q->where('id', '!=', $supervisorEmployeeId))
                    ->update(['parent_id' => $supervisorEmployeeId ?: null]);
            }

            event(new TeamUpdated($team));

            return $team->fresh();
        });
    }
}
