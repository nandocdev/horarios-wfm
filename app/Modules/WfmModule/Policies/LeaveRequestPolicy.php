<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\LeaveRequest;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('leave_requests.viewAny');
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('leave_requests.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('leave_requests.create');
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('leave_requests.update');
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('leave_requests.delete');
    }

    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('leave_requests.approve');
    }

    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->hasPermissionTo('leave_requests.approve');
    }
}
