<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Events\ReactionAdded;
use App\Modules\CommunicationsModule\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para enviar notificaciones cuando se agrega una reacción.
 */
class SendReactionNotificationListener implements ShouldQueue
{
    public function handle(ReactionAdded $event): void
    {
        $reaction = $event->reaction;

        // Notificar al autor del shoutout si no es el mismo que reacciona
        if ($reaction->shoutout->user_id !== $reaction->user_id) {
            Notification::create([
                'user_id' => $reaction->shoutout->user_id,
                'type' => 'reaction_on_shoutout',
                'title' => 'Nueva reacción en tu shoutout',
                'message' => "{$reaction->user->name} reaccionó con {$reaction->type->value} a tu shoutout",
                'data' => [
                    'reaction_id' => $reaction->id,
                    'shoutout_id' => $reaction->shoutout_id,
                    'reactor_id' => $reaction->user_id,
                    'reaction_type' => $reaction->type->value,
                ],
                'expires_at' => now()->addDays(3),
            ]);
        }
    }
}
