<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Actions;

use App\Modules\OrganizationModule\Events\DepartmentStatusToggled;
use App\Modules\OrganizationModule\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el estado activo/inactivo de un departamento.
 */
class ToggleDepartmentStatusAction
{
    /**
     * Ejecuta el cambio de estado del departamento.
     *
     * @param  Department  $department  Departamento a cambiar estado
     * @return Department Departamento con estado actualizado
     */
    public function execute(Department $department): Department
    {
        return DB::transaction(function () use ($department) {
            $department->update([
                'is_active' => ! $department->is_active,
            ]);

            event(new DepartmentStatusToggled($department));

            return $department->fresh();
        });
    }
}
