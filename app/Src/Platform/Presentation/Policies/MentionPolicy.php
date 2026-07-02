<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Mention;
use App\Modules\CoreModule\Models\User;

final class MentionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('mentions.view');
    }

    public function view(User $user, Mention $mention): bool
    {
        return $user->can('mentions.view') ||
            $user->id === $mention->mentioned_user_id ||
            $user->id === $mention->mentioner_user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('mentions.create');
    }

    public function update(User $user, Mention $mention): bool
    {
        return $user->can('mentions.edit') || $user->id === $mention->mentioned_user_id;
    }

    public function delete(User $user, Mention $mention): bool
    {
        return $user->can('mentions.delete') || $user->id === $mention->mentioner_user_id;
    }
}
