<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Tag;
use App\Modules\CoreModule\Models\User;

final class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('communications.manage');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can('communications.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('communications.manage');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('communications.manage');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('communications.manage');
    }
}
