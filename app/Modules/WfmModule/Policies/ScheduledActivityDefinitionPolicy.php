<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;

class ScheduledActivityDefinitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.scheduled_defs');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.scheduled_defs');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.scheduled_defs');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.scheduled_defs');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('wfm.catalogs.scheduled_defs');
    }
}
