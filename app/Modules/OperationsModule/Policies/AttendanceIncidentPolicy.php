<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\OperationsModule\Models\AttendanceIncident;

class AttendanceIncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('attendance_incidents.viewAny');
    }

    public function view(User $user, AttendanceIncident $attendanceIncident): bool
    {
        return $user->hasPermissionTo('attendance_incidents.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('attendance_incidents.create');
    }

    public function update(User $user, AttendanceIncident $attendanceIncident): bool
    {
        return $user->hasPermissionTo('attendance_incidents.update');
    }

    public function delete(User $user, AttendanceIncident $attendanceIncident): bool
    {
        return $user->hasPermissionTo('attendance_incidents.delete');
    }
}
