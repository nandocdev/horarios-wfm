<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Policies;

use App\Modules\CoreModule\Models\User;

class OperationalSettingPolicy
{
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('wfm.settings.manage');
    }
}
