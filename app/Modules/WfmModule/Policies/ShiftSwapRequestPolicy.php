<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\WfmModule\Models\ShiftSwapRequest;

class ShiftSwapRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.viewAny');
    }

    public function view(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.create');
    }

    public function update(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.update');
    }

    public function delete(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.delete');
    }

    public function approve(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.approve');
    }

    public function reject(User $user, ShiftSwapRequest $shiftSwapRequest): bool
    {
        return $user->hasPermissionTo('shift_swap_requests.approve');
    }
}
