<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\DTOs\DirectorateDTO;
use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crea una nueva dirección en el sistema.
 *
 * @throws QueryException
 */
class CreateDirectorateAction
{
    /**
     * Ejecuta la creación de la dirección.
     *
     * @param  DirectorateDTO  $dto  Datos validados de la dirección
     * @return Directorate Dirección creada y persistida
     */
    public function execute(DirectorateDTO $dto): Directorate
    {
        return DB::transaction(function () use ($dto) {
            return Directorate::create([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => property_exists($dto, 'is_active') ? $dto->is_active : true,
            ]);
        });
    }
}
