<?php

declare(strict_types=1);

namespace App\Shared\Notifications;

use App\Shared\DTOs\NotificationDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NotificationDTO $dto
    ) {}

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if (config('services.webex.bot_token') && config('services.webex.room_id')) {
            $channels[] = 'webex';
        }

        // Si el notifiable tiene email y es un usuario, opcionalmente podríamos forzar 'mail' aquí.
        // Pero para UI events, dejamos que las subclases agreguen 'mail' si es necesario.
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->dto->title)
            ->greeting('Hola, '.($notifiable->first_name ?? 'colaborador'))
            ->line($this->dto->message);

        if ($this->dto->summary) {
            $message->line($this->dto->summary);
        }

        if (! empty($this->dto->facts)) {
            foreach ($this->dto->facts as $fact) {
                if (isset($fact['label']) && isset($fact['value'])) {
                    $message->line("**{$fact['label']}:** {$fact['value']}");
                }
            }
        }

        if (! empty($this->dto->recommendation)) {
            $message->line($this->dto->recommendation);
        }

        if (! empty($this->dto->actionUrl)) {
            $message->action('Ver Detalles', $this->dto->actionUrl);
        }

        return $message;
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
