<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\DirectoryModule\Models\Unit;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('directory.manage');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('directory.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('directory.manage');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('directory.manage');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->hasPermissionTo('directory.manage');
    }
}
