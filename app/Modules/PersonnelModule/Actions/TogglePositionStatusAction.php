<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Events\PositionStatusToggled;
use App\Modules\PersonnelModule\Models\Position;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el estado activo/inactivo de una posición.
 */
class TogglePositionStatusAction
{
    /**
     * Ejecuta el cambio de estado de la posición.
     *
     * @param  Position  $position  Posición a cambiar estado
     * @return Position Posición con estado actualizado
     */
    public function execute(Position $position): Position
    {
        return DB::transaction(function () use ($position) {
            $position->update([
                'is_active' => ! $position->is_active,
            ]);

            event(new PositionStatusToggled($position));

            return $position->fresh();
        });
    }
}
