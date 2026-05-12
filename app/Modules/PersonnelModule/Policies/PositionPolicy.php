<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Position;

/**
 * Define los permisos de acceso al recurso Position.
 * Registrar en ModuleServiceProvider.
 */
class PositionPolicy
{
    /**
     * Determina si el usuario puede ver cualquier position.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('positions.viewAny');
    }

    /**
     * Determina si el usuario puede ver un position específico.
     */
    public function view(User $user, Position $position): bool
    {
        return $user->hasPermissionTo('positions.viewAny');
    }

    /**
     * Determina si el usuario puede crear positions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('positions.create');
    }

    /**
     * Determina si el usuario puede actualizar un position.
     */
    public function update(User $user, Position $position): bool
    {
        return $user->hasPermissionTo('positions.update');
    }

    /**
     * Determina si el usuario puede eliminar un position.
     */
    public function delete(User $user, Position $position): bool
    {
        return $user->hasPermissionTo('positions.delete');
    }
}
