<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Policies;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\CoreModule\Models\User;

class CallQueuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('call_queues.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('call_queues.manage');
    }

    public function update(User $user, CallQueue $queue): bool
    {
        return $user->hasPermissionTo('call_queues.manage');
    }

    public function delete(User $user, CallQueue $queue): bool
    {
        return $user->hasPermissionTo('call_queues.manage');
    }
}
