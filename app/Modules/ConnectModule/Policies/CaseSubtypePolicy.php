<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Policies;

use App\Modules\ConnectModule\Models\CaseSubtype;
use App\Modules\CoreModule\Models\User;

class CaseSubtypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('case_subtypes.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('case_subtypes.manage');
    }

    public function update(User $user, CaseSubtype $caseSubtype): bool
    {
        return $user->hasPermissionTo('case_subtypes.manage');
    }

    public function delete(User $user, CaseSubtype $caseSubtype): bool
    {
        return $user->hasPermissionTo('case_subtypes.manage');
    }
}
