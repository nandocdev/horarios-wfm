<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;

class ActivityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.activities');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.activities');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.activities');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.activities');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.activities');
    }
}
