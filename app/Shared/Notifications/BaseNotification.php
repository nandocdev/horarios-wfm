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
        $channels = ['database', 'broadcast'];

        if (config('services.webex.bot_token')) {
            $channels[] = 'webex';
        }

        return $channels;
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

    public function toWebex($notifiable): ?string
    {
        $title = $this->dto->title;
        $message = $this->dto->message;

        return "*{$title}*\n\n{$message}";
    }
}
