<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Notifications;

use App\Shared\Notifications\BaseNotification;

class SyncFailedNotification extends BaseNotification
{
    public function via($notifiable): array
    {
        return parent::via($notifiable);
    }
}
