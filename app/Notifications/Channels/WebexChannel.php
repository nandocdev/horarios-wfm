<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

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

        if (is_string($message)) {
            $this->webex->sendText($message);

            return;
        }

        if (isset($message['markdown'])) {
            $this->webex->sendMarkdown($message['markdown']);

            return;
        }

        $this->webex->sendText($message['text'] ?? '');
    }
}
