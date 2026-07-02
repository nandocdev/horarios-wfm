<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Policies;

use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CoreModule\Models\User;

final class InAppNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notifications.view');
    }

    public function view(User $user, Notification $notification): bool
    {
        return $user->can('notifications.view') || $user->id === $notification->user_id;
    }

    public function update(User $user, Notification $notification): bool
    {
        return $user->can('notifications.edit') || $user->id === $notification->user_id;
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $user->can('notifications.delete') || $user->id === $notification->user_id;
    }
}
