<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\Events\DirectorateStatusToggled;
use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el estado activo/inactivo de una dirección.
 */
class ToggleDirectorateStatusAction
{
    /**
     * Ejecuta el cambio de estado de la dirección.
     *
     * @param  Directorate  $directorate  Dirección a cambiar estado
     * @return Directorate Dirección con estado actualizado
     */
    public function execute(Directorate $directorate): Directorate
    {
        return DB::transaction(function () use ($directorate) {
            $directorate->update([
                'is_active' => ! $directorate->is_active,
            ]);

            event(new DirectorateStatusToggled($directorate));

            return $directorate->fresh();
        });
    }
}
