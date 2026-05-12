<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso Comment.
 */
class CommentPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('comments.view');
    }

    public function view(User $authUser, Comment $comment): bool
    {
        return $authUser->hasPermissionTo('comments.view') ||
            $authUser->id === $comment->user_id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('comments.create');
    }

    public function update(User $authUser, Comment $comment): bool
    {
        return $authUser->hasPermissionTo('comments.edit') ||
            $authUser->id === $comment->user_id;
    }

    public function delete(User $authUser, Comment $comment): bool
    {
        return $authUser->hasPermissionTo('comments.delete') ||
            $authUser->id === $comment->user_id;
    }

    public function restore(User $authUser, Comment $comment): bool
    {
        return $authUser->hasPermissionTo('comments.restore');
    }

    public function forceDelete(User $authUser, Comment $comment): bool
    {
        return $authUser->hasPermissionTo('comments.force_delete');
    }
}
