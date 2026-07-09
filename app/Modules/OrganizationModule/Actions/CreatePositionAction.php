<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\DTOs\PositionDTO;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Position;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crea un nuevo cargo en el sistema.
 *
 * Valida que el departamento exista antes de crear el cargo.
 *
 * @throws QueryException
 * @throws ModelNotFoundException
 */
class CreatePositionAction
{
    /**
     * Ejecuta la creación del cargo.
     *
     * @param  PositionDTO  $dto  Datos validados del cargo
     * @return Position Cargo creado y persistido
     */
    public function execute(PositionDTO $dto): Position
    {
        // Validar que el departamento existe
        Department::findOrFail($dto->department_id);

        return DB::transaction(function () use ($dto) {
            return Position::create([
                'department_id' => $dto->department_id,
                'name' => $dto->name,
                'position_code' => $dto->position_code,
                'description' => $dto->description,
            ]);
        });
    }
}
