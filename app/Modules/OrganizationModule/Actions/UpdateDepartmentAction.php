<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\DTOs\DepartmentDTO;
use App\Modules\OrganizationModule\Events\DepartmentUpdated;
use App\Modules\OrganizationModule\Models\Department;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza un departamento existente en el sistema.
 *
 * @throws QueryException
 */
class UpdateDepartmentAction
{
    /**
     * Ejecuta la actualización del departamento.
     *
     * @param  Department  $department  Departamento a actualizar
     * @param  DepartmentDTO  $dto  Datos validados para la actualización
     * @return Department Departamento actualizado y persistido
     */
    public function execute(Department $department, DepartmentDTO $dto): Department
    {
        return DB::transaction(function () use ($department, $dto) {
            $department->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'directorate_id' => $dto->directorate_id,
            ]);

            event(new DepartmentUpdated($department));

            return $department->fresh(['directorate']);
        });
    }
}
