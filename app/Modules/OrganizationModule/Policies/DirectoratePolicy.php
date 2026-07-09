<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\OrganizationModule\Models\Directorate;

/**
 * Define los permisos de acceso al recurso Directorate.
 * Registrar en ModuleServiceProvider.
 */
class DirectoratePolicy
{
    /**
     * Determina si el usuario puede ver cualquier directorate.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('directorates.viewAny');
    }

    /**
     * Determina si el usuario puede ver un directorate específico.
     */
    public function view(User $user, Directorate $directorate): bool
    {
        return $user->hasPermissionTo('directorates.viewAny');
    }

    /**
     * Determina si el usuario puede crear directorates.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('directorates.create');
    }

    /**
     * Determina si el usuario puede actualizar un directorate.
     */
    public function update(User $user, Directorate $directorate): bool
    {
        return $user->hasPermissionTo('directorates.update');
    }

    /**
     * Determina si el usuario puede eliminar un directorate.
     */
    public function delete(User $user, Directorate $directorate): bool
    {
        return $user->hasPermissionTo('directorates.delete');
    }
}
