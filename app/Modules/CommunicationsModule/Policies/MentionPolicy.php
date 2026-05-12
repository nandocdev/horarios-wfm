<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Mention;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso Mention.
 */
class MentionPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('mentions.view');
    }

    public function view(User $authUser, Mention $mention): bool
    {
        return $authUser->hasPermissionTo('mentions.view') ||
            $authUser->id === $mention->mentioned_user_id ||
            $authUser->id === $mention->mentioner_user_id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('mentions.create');
    }

    public function update(User $authUser, Mention $mention): bool
    {
        return $authUser->hasPermissionTo('mentions.edit') ||
            $authUser->id === $mention->mentioned_user_id;
    }

    public function delete(User $authUser, Mention $mention): bool
    {
        return $authUser->hasPermissionTo('mentions.delete') ||
            $authUser->id === $mention->mentioner_user_id;
    }

    public function restore(User $authUser, Mention $mention): bool
    {
        return $authUser->hasPermissionTo('mentions.restore');
    }

    public function forceDelete(User $authUser, Mention $mention): bool
    {
        return $authUser->hasPermissionTo('mentions.force_delete');
    }
}
