<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Reaction;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso Reaction.
 */
class ReactionPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('reactions.view');
    }

    public function view(User $authUser, Reaction $reaction): bool
    {
        return $authUser->hasPermissionTo('reactions.view') ||
            $authUser->id === $reaction->user_id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('reactions.create');
    }

    public function update(User $authUser, Reaction $reaction): bool
    {
        return $authUser->hasPermissionTo('reactions.edit') ||
            $authUser->id === $reaction->user_id;
    }

    public function delete(User $authUser, Reaction $reaction): bool
    {
        return $authUser->hasPermissionTo('reactions.delete') ||
            $authUser->id === $reaction->user_id;
    }

    public function restore(User $authUser, Reaction $reaction): bool
    {
        return $authUser->hasPermissionTo('reactions.restore');
    }

    public function forceDelete(User $authUser, Reaction $reaction): bool
    {
        return $authUser->hasPermissionTo('reactions.force_delete');
    }
}
