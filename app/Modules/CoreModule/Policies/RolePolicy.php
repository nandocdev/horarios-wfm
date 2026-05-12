<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Policies;

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Controla la autorización para la gestión de Roles y Permisos.
 */
class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('roles.view');
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('roles.create');
    }

    public function update(User $authUser, Role $role): bool
    {
        if (! $authUser->hasPermissionTo('roles.edit')) {
            return false;
        }

        // No puedes editar roles de jerarquía superior a la tuya
        $authMaxHierarchy = $authUser->roles()->min('hierarchy_level') ?? 0;
        $targetHierarchy = (int) ($role->hierarchy_level ?? 0);

        return $authMaxHierarchy >= $targetHierarchy;
    }
}
