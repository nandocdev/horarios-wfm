<?php

declare(strict_types=1);

namespace App\Shared\Notifications\Concerns;

trait HasWebexSupport
{
    public function toWebex($notifiable): ?string
    {
        $title = $this->payload['title'] ?? 'Notificación';
        $message = $this->payload['message'] ?? '';
        $summary = $this->payload['summary'] ?? null;
        $facts = $this->payload['facts'] ?? [];
        $recommendation = $this->payload['recommendation'] ?? null;

        if (empty($message) && empty($summary) && empty($facts)) {
            return null;
        }

        $lines = [];

        $lines[] = "*{$title}*";
        $lines[] = '';

        if ($summary) {
            $lines[] = $summary;
            $lines[] = '';
        } else {
            $lines[] = $message;
            $lines[] = '';
        }

        if (! empty($facts)) {
            $lines[] = '---';
            $lines[] = '';

            foreach ($facts as $fact) {
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

        if ($recommendation) {
            $lines[] = $recommendation;
        }

        return implode("\n", $lines);
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
