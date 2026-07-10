<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;

class WeeklySchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('schedules.manage');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('schedules.manage');
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
}
