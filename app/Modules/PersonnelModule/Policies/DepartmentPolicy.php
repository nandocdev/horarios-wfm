<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Department;

/**
 * Define los permisos de acceso al recurso Department.
 * Registrar en ModuleServiceProvider.
 */
class DepartmentPolicy
{
    /**
     * Determina si el usuario puede ver cualquier department.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('departments.viewAny');
    }

    /**
     * Determina si el usuario puede ver un department específico.
     */
    public function view(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.viewAny');
    }

    /**
     * Determina si el usuario puede crear departments.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('departments.create');
    }

    /**
     * Determina si el usuario puede actualizar un department.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.update');
    }

    /**
     * Determina si el usuario puede eliminar un department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.delete');
    }
}
