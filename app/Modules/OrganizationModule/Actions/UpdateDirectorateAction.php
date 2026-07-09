<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Events\DirectorateUpdated;
use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza una dirección existente en el sistema.
 *
 * @throws QueryException
 */
class UpdateDirectorateAction
{
    /**
     * Ejecuta la actualización de la dirección.
     *
     * @param  Directorate  $directorate  Dirección a actualizar
     * @param  DirectorateDTO  $dto  Datos validados para la actualización
     * @return Directorate Dirección actualizada y persistida
     */
    public function execute(Directorate $directorate, DirectorateDTO $dto): Directorate
    {
        return DB::transaction(function () use ($directorate, $dto) {
            $directorate->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => property_exists($dto, 'is_active') ? $dto->is_active : true,
            ]);

            event(new DirectorateUpdated($directorate));

            return $directorate->fresh();
        });
    }
}
