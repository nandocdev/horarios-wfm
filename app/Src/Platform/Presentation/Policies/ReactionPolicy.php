<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Reaction;
use App\Modules\CoreModule\Models\User;

final class ReactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reactions.view');
    }

    public function view(User $user, Reaction $reaction): bool
    {
        return $user->can('reactions.view') || $user->id === $reaction->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('reactions.create');
    }

    public function update(User $user, Reaction $reaction): bool
    {
        return $user->can('reactions.edit') || $user->id === $reaction->user_id;
    }

    public function delete(User $user, Reaction $reaction): bool
    {
        return $user->can('reactions.delete') || $user->id === $reaction->user_id;
    }
}
