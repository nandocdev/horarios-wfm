<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Policies;

use App\Src\Identity\Domain\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ChannelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('channels.view_any');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('channels.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('channels.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('channels.update');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('channels.delete');
    }
}
