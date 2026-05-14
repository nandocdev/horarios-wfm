<?php

declare(strict_types=1);

namespace App\Shared\Notifications;

use App\Shared\DTOs\NotificationDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected NotificationDTO $dto
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->dto->toArray();
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'title' => $this->dto->title,
            'message' => $this->dto->message,
            'level' => $this->dto->level,
            'icon' => $this->dto->icon,
            'action_url' => $this->dto->actionUrl,
        ]);
    }
}
