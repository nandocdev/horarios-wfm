<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Policies;

use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(EloquentUser $authUser): bool
    {
        return $authUser->hasPermissionTo('users.view');
    }

    public function view(EloquentUser $authUser, EloquentUser $target): bool
    {
        return $authUser->hasPermissionTo('users.view') || $authUser->id === $target->id;
    }

    public function create(EloquentUser $authUser): bool
    {
        return $authUser->hasPermissionTo('users.create');
    }

    public function update(EloquentUser $authUser, EloquentUser $target): bool
    {
        if (! $authUser->hasPermissionTo('users.edit')) {
            return $authUser->id === $target->id;
        }

        return $this->hierarchyCheck($authUser, $target);
    }

    public function delete(EloquentUser $authUser, EloquentUser $target): bool
    {
        return $authUser->hasPermissionTo('users.delete') && $this->hierarchyCheck($authUser, $target);
    }

    public function forceDelete(EloquentUser $authUser, EloquentUser $target): bool
    {
        return $authUser->hasRole('admin');
    }

    public function restore(EloquentUser $authUser, EloquentUser $target): bool
    {
        return $authUser->hasPermissionTo('users.edit');
    }

    protected function hierarchyCheck(EloquentUser $authUser, EloquentUser $target): bool
    {
        if ($authUser->id === $target->id) {
            return true;
        }

        $authMaxHierarchy = $authUser->roles()->min('hierarchy_level') ?? 0;
        $targetMaxHierarchy = $target->roles()->min('hierarchy_level') ?? 0;

        return $authMaxHierarchy >= $targetMaxHierarchy;
    }
}
