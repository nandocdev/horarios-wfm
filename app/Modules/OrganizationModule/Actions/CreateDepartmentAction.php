<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\DTOs\DepartmentDTO;
use App\Modules\OrganizationModule\Models\Department;
use App\Modules\OrganizationModule\Models\Directorate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Crea un nuevo departamento en el sistema.
 *
 * Valida que la dirección exista antes de crear el departamento.
 *
 * @throws QueryException
 * @throws ModelNotFoundException
 */
class CreateDepartmentAction
{
    /**
     * Ejecuta la creación del departamento.
     *
     * @param  DepartmentDTO  $dto  Datos validados del departamento
     * @return Department Departamento creado y persistido
     */
    public function execute(DepartmentDTO $dto): Department
    {
        // Validar que la dirección existe
        Directorate::findOrFail($dto->directorate_id);

        return DB::transaction(function () use ($dto) {
            return Department::create([
                'directorate_id' => $dto->directorate_id,
                'name' => $dto->name,
                'description' => $dto->description,
            ]);
        });
    }
}
