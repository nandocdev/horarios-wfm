<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Policies;

use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\CoreModule\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class CallRecordPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'call_records.viewAny')
            || $this->hasPermission($user, 'call_records.update');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'call_records.create')
            || $this->hasPermission($user, 'call_records.update');
    }

    public function view(User $user, CallRecord $callRecord): bool
    {
        return $this->viewAny($user)
            || $user->employee?->id === $callRecord->employee_id;
    }

    public function update(User $user, CallRecord $callRecord): bool
    {
        if (! $this->hasPermission($user, 'call_records.update')) {
            return false;
        }

        if ($callRecord->employee_id === null) {
            return true;
        }

        return $user->employee?->id === $callRecord->employee_id
            || $user->hasAnyRole(['wfm', 'director', 'coordinator']);
    }

    private function hasPermission(User $user, string $permissionName): bool
    {
        try {
            return $user->hasPermissionTo($permissionName);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
