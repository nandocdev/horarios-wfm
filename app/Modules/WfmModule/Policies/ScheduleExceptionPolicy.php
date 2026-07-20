<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\ScheduleException;

class ScheduleExceptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('schedule_exceptions.viewAny');
    }

    public function view(User $user, ScheduleException $scheduleException): bool
    {
        return $user->hasPermissionTo('schedule_exceptions.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('schedule_exceptions.create');
    }

    public function update(User $user, ScheduleException $scheduleException): bool
    {
        return $user->hasPermissionTo('schedule_exceptions.update');
    }

    public function delete(User $user, ScheduleException $scheduleException): bool
    {
        return $user->hasPermissionTo('schedule_exceptions.delete');
    }
}
