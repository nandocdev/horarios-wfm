<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Policies;

use App\Modules\CoreModule\Models\User;
use App\Modules\PersonnelModule\Models\EmploymentStatus;

class EmploymentStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employment_statuses.viewAny');
    }

    public function view(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->hasPermissionTo('employment_statuses.viewAny');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employment_statuses.create');
    }

    public function update(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->hasPermissionTo('employment_statuses.update');
    }

    public function delete(User $user, EmploymentStatus $employmentStatus): bool
    {
        return $user->hasPermissionTo('employment_statuses.delete');
    }
}
