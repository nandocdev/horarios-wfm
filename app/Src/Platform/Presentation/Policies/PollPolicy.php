<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CoreModule\Models\User;

final class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('polls.manage');
    }

    public function view(User $user, Poll $poll): bool
    {
        return $user->can('polls.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('polls.manage');
    }

    public function update(User $user, Poll $poll): bool
    {
        return $user->can('polls.manage');
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $user->can('polls.manage');
    }
}
