<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CoreModule\Models\User;

final class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('comments.view');
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->can('comments.view') || $user->id === $comment->user_id;
    }

    public function create(User $user): bool
    {
        return $user->can('comments.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->can('comments.edit') || $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->can('comments.delete') || $user->id === $comment->user_id;
    }
}
