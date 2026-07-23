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

        if (config('services.webex.bot_token') && config('services.webex.room_id')) {
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
            'notificationType' => $this->dto->notificationType,
            'summary' => $this->dto->summary,
            'facts' => $this->dto->facts,
            'recommendation' => $this->dto->recommendation,
            'actions' => $this->dto->actions,
            'resourceType' => $this->dto->resourceType,
            'resourceId' => $this->dto->resourceId,
        ]);
    }

    public function toWebex($notifiable): ?string
    {
        $lines = [];

        $lines[] = "*{$this->dto->title}*";
        $lines[] = '';

        if ($this->dto->summary) {
            $lines[] = $this->dto->summary;
            $lines[] = '';
        } else {
            $lines[] = $this->dto->message;
            $lines[] = '';
        }

        $lines[] = '---';
        $lines[] = '';

        if (! empty($this->dto->facts)) {
            foreach ($this->dto->facts as $fact) {
                $label = $fact['label'] ?? '';
                $value = $fact['value'] ?? '';
                if ($label && $value) {
                    $lines[] = "**{$label}:** {$value}";
                }
            }
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
        }

        if ($this->dto->recommendation) {
            $lines[] = $this->dto->recommendation;
        }

        return implode("\n", $lines);
    }
}
