<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Notification;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Notification.
 * Maneja efectos secundarios como caché y expiración automática.
 */
class NotificationObserver
{
    public function created(Notification $notification): void
    {
        Cache::forget("user_notifications:{$notification->user_id}");
        Cache::forget('notifications_recent');
    }

    public function updated(Notification $notification): void
    {
        Cache::forget("notification:{$notification->id}");
        Cache::forget("user_notifications:{$notification->user_id}");
    }

    public function deleted(Notification $notification): void
    {
        Cache::forget("notification:{$notification->id}");
        Cache::forget("user_notifications:{$notification->user_id}");
        Cache::forget('notifications_recent');
    }
}
