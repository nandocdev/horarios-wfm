<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Events\TeamStatusToggled;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el estado activo/inactivo de un equipo.
 */
class ToggleTeamStatusAction
{
    /**
     * Ejecuta el cambio de estado del equipo.
     *
     * @param  Team  $team  Equipo a cambiar de estado
     * @return Team Equipo con el estado actualizado
     */
    public function execute(Team $team): Team
    {
        return DB::transaction(function () use ($team) {
            $team->update([
                'is_active' => ! $team->is_active,
            ]);

            event(new TeamStatusToggled($team));

            return $team->fresh();
        });
    }
}
