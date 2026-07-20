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

            // Si el supervisor cambió, actualizamos la jerarquía de todos los miembros activos excepto el supervisor mismo
            if ($oldSupervisorId !== $dto->supervisor_id) {
                Employee::whereHas('currentTeamMember', function ($q) use ($team) {
                    $q->where('team_id', $team->id);
                })
                    ->where('id', '!=', $dto->supervisor_id)
                    ->update(['parent_id' => $dto->supervisor_id]);
            }

            event(new TeamUpdated($team));

            return $team->fresh();
        });
    }
}
