<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\DTOs\PositionDTO;
use App\Modules\OrganizationModule\Events\PositionUpdated;
use App\Modules\OrganizationModule\Models\Position;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza una posición existente en el sistema.
 *
 * @throws QueryException
 */
class UpdatePositionAction
{
    /**
     * Ejecuta la actualización de la posición.
     *
     * @param  Position  $position  Posición a actualizar
     * @param  PositionDTO  $dto  Datos validados para la actualización
     * @return Position Posición actualizada y persistida
     */
    public function execute(Position $position, PositionDTO $dto): Position
    {
        return DB::transaction(function () use ($position, $dto) {
            $position->update([
                'name' => $dto->name,
                'position_code' => $dto->position_code,
                'description' => $dto->description,
                'department_id' => $dto->department_id,
                'is_active' => $dto->is_active,
            ]);

            event(new PositionUpdated($position));

            return $position->fresh(['department.directorate']);
        });
    }
}
