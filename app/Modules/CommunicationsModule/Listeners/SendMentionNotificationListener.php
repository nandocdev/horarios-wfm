<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Events\MentionCreated;
use App\Modules\CommunicationsModule\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para enviar notificaciones cuando se crea una mención.
 */
class SendMentionNotificationListener implements ShouldQueue
{
    public function handle(MentionCreated $event): void
    {
        $mention = $event->mention;

        // Solo notificar si no ha sido leída aún
        if (! $mention->is_read) {
            $message = match ($mention->mentionable_type) {
                'App\Modules\CommunicationsModule\Models\News' => "{$mention->mentioner->name} te mencionó en una noticia",
                'App\Modules\CommunicationsModule\Models\Shoutout' => "{$mention->mentioner->name} te mencionó en un shoutout",
                'App\Modules\CommunicationsModule\Models\Comment' => "{$mention->mentioner->name} te mencionó en un comentario",
                default => "{$mention->mentioner->name} te mencionó",
            };

            Notification::create([
                'user_id' => $mention->mentioned_user_id,
                'type' => 'mention',
                'title' => 'Nueva mención',
                'message' => $message,
                'data' => [
                    'mention_id' => $mention->id,
                    'mentionable_type' => $mention->mentionable_type,
                    'mentionable_id' => $mention->mentionable_id,
                    'mentioner_id' => $mention->mentioner_user_id,
                    'context' => $mention->context,
                ],
                'expires_at' => now()->addDays(7),
            ]);
        }
    }
}
