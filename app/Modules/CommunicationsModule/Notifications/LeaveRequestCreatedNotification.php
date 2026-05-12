<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestCreatedNotification extends Notification
{
    public function __construct(public readonly array $payload) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload);
    }
}
