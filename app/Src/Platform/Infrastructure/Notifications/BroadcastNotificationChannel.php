<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;

final class BroadcastNotificationChannel {
    public function send(mixed $notifiable, string $title, string $message, string $level = 'info', string $icon = 'information-circle', string $actionUrl = '#'): void {
        $notifiable->notify(new class ($title, $message, $level, $icon, $actionUrl) extends \Illuminate\Notifications\Notification {
            public function __construct(
            private string $title,
            private string $message,
            private string $level,
            private string $icon,
            private string $actionUrl,
            ) {}

            public function via($notifiable): array {
                return ['database', 'broadcast'];
            }

            public function toDatabase($notifiable): array {
                return [
                'title' => $this->title,
                'message' => $this->message,
                'level' => $this->level,
                'icon' => $this->icon,
                'action_url' => $this->actionUrl,
                ];
            }

            public function toBroadcast($notifiable): BroadcastMessage {
                return new BroadcastMessage([
                    'title' => $this->title,
                    'message' => $this->message,
                    'level' => $this->level,
                    'icon' => $this->icon,
                    'action_url' => $this->actionUrl,
                ]);
            }
        });
    }
}
