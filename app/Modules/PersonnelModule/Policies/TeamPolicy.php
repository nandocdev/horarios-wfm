<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\Team;

/**
 * Define los permisos de acceso al recurso Team.
 * Registrar en ModuleServiceProvider.
 */
class TeamPolicy
{
    /**
     * Determina si el usuario puede ver cualquier team.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('teams.viewAny');
    }

    /**
     * Determina si el usuario puede ver un team específico.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('teams.viewAny');
    }

    /**
     * Determina si el usuario puede crear teams.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('teams.create');
    }

    /**
     * Determina si el usuario puede actualizar un team.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('teams.update');
    }

    /**
     * Determina si el usuario puede eliminar un team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->hasPermissionTo('teams.delete');
    }
}
