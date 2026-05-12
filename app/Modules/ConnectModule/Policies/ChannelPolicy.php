<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Policies;

use App\Modules\CoreModule\Models\User;

class ChannelPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('channels.manage');
    }
}
