<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Notifications;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;

final class EmailNotificationChannel {
    public function send(string $to, string $subject, string $body, array $attachments = []): void {
        Mail::send([], [], function ($message) use ($to, $subject, $body, $attachments) {
            $message->to($to)
                ->subject($subject)
                ->html($body);

            foreach ($attachments as $attachment) {
                $message->attach($attachment['path'], [
                    'as' => $attachment['name'] ?? basename($attachment['path']),
                    'mime' => $attachment['mime'] ?? null,
                ]);
            }
        });
    }

    public function sendMailable(Mailable $mailable): void {
        Mail::send($mailable);
    }
}
