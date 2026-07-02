<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CoreModule\Models\User;

final class ShoutoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('shoutouts.manage');
    }

    public function view(User $user, Shoutout $shoutout): bool
    {
        return $user->can('shoutouts.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('shoutouts.manage');
    }

    public function update(User $user, Shoutout $shoutout): bool
    {
        return $user->can('shoutouts.manage');
    }

    public function delete(User $user, Shoutout $shoutout): bool
    {
        return $user->can('shoutouts.manage');
    }
}
