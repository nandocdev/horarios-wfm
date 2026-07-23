<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Modules\CoreModule\Models\User;
use App\Services\WebexService;
use Illuminate\Notifications\Notification;

class WebexChannel
{
    public function __construct(
        protected WebexService $webex
    ) {}

    public function send($notifiable, Notification $notification): void
    {
        $message = $notification->toWebex($notifiable);

        if ($message === null) {
            return;
        }

        $payload = [];

        if (is_string($message)) {
            $payload['markdown'] = $message;
        } elseif (isset($message['markdown'])) {
            $payload['markdown'] = $message['markdown'];
        } else {
            $payload['markdown'] = $message['text'] ?? '';
        }

        if ($notifiable instanceof User && $notifiable->email) {
            $payload['toPersonEmail'] = $notifiable->email;
        }

        $this->webex->sendDirect($payload);
    }
}
