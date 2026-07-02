<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Policies;

use App\Src\Identity\Infrastructure\Persistence\EloquentRole;
use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(EloquentUser $authUser): bool
    {
        return $authUser->hasPermissionTo('roles.view');
    }

    public function create(EloquentUser $authUser): bool
    {
        return $authUser->hasPermissionTo('roles.create');
    }

    public function update(EloquentUser $authUser, EloquentRole $role): bool
    {
        if (! $authUser->hasPermissionTo('roles.edit')) {
            return false;
        }

        $authMaxHierarchy = $authUser->roles()->min('hierarchy_level') ?? 0;
        $targetHierarchy = (int) ($role->hierarchy_level ?? 0);

        return $authMaxHierarchy >= $targetHierarchy;
    }

    public function delete(EloquentUser $authUser, EloquentRole $role): bool
    {
        if (! $authUser->hasPermissionTo('roles.delete')) {
            return false;
        }

        $authMaxHierarchy = $authUser->roles()->min('hierarchy_level') ?? 0;
        $targetHierarchy = (int) ($role->hierarchy_level ?? 0);

        return $authMaxHierarchy >= $targetHierarchy;
    }
}
