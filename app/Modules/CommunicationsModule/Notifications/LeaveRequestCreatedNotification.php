<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Notifications;

use App\Shared\Notifications\Concerns\HasWebexSupport;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestCreatedNotification extends Notification
{
    use HasWebexSupport;

    public function __construct(public readonly array $payload) {}

    public function toDatabase($notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload);
    }
}
