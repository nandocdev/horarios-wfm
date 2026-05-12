<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Policies;

use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CoreModule\Models\User;

/**
 * Define los permisos de acceso al recurso Notification.
 */
class NotificationPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('notifications.view');
    }

    public function view(User $authUser, Notification $notification): bool
    {
        return $authUser->hasPermissionTo('notifications.view') ||
            $authUser->id === $notification->user_id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('notifications.create');
    }

    public function update(User $authUser, Notification $notification): bool
    {
        return $authUser->hasPermissionTo('notifications.edit') ||
            $authUser->id === $notification->user_id;
    }

    public function delete(User $authUser, Notification $notification): bool
    {
        return $authUser->hasPermissionTo('notifications.delete') ||
            $authUser->id === $notification->user_id;
    }

    public function restore(User $authUser, Notification $notification): bool
    {
        return $authUser->hasPermissionTo('notifications.restore');
    }

    public function forceDelete(User $authUser, Notification $notification): bool
    {
        return $authUser->hasPermissionTo('notifications.force_delete');
    }
}
