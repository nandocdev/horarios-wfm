<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('schedules.manage');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('schedules.view_own')
            || $user->hasPermissionTo('schedules.view_team')
            || $user->hasPermissionTo('schedules.view_all')
            || $user->hasPermissionTo('schedules.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('schedules.manage');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('schedules.manage');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('schedules.manage');
    }

    public function monitorRealtime(User $user): bool
    {
        return $user->hasPermissionTo('realtime.view')
            || $user->hasPermissionTo('schedules.manage')
            || ($user->employee?->hasCoordinatorRights() ?? false);
    }
}
