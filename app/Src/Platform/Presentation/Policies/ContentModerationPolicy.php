<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;

final class ContentModerationPolicy
{
    public function moderate(User $user): bool
    {
        return $user->can('communications.moderate');
    }

    public function approve(User $user): bool
    {
        return $user->can('communications.approve');
    }

    public function reject(User $user): bool
    {
        return $user->can('communications.reject');
    }

    public function archive(User $user): bool
    {
        return $user->can('communications.archive');
    }

    public function viewPending(User $user): bool
    {
        return $user->can('communications.view_pending');
    }

    public function moderateContent(User $user, Model $content): bool
    {
        return $user->can('communications.moderate');
    }
}
