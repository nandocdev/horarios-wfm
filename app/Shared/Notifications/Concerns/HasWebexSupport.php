<?php

declare(strict_types=1);

namespace App\Shared\Notifications\Concerns;

trait HasWebexSupport
{
    public function toWebex($notifiable): ?string
    {
        $title = $this->payload['title'] ?? 'Notificación';
        $message = $this->payload['message'] ?? '';

        if (empty($message)) {
            return null;
        }

        return "*{$title}*\n\n{$message}";
    }

    public function via($notifiable): array
    {
        $channels = ['database', 'broadcast'];

        if (method_exists($this, 'additionalVia')) {
            $channels = array_merge($channels, $this->additionalVia());
        }

        if (config('services.webex.bot_token') && config('services.webex.room_id')) {
            $channels[] = 'webex';
        }

        return $channels;
    }
}
