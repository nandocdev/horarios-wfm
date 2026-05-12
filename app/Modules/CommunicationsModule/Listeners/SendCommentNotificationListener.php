<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Models\User;
use App\Modules\CommunicationsModule\Events\CommentCreated;
use App\Modules\CommunicationsModule\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para enviar notificaciones cuando se crea un comentario.
 */
class SendCommentNotificationListener implements ShouldQueue
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;

        // Notificar al autor de la news si no es el mismo que comenta
        if ($comment->news->user_id !== $comment->user_id) {
            Notification::create([
                'user_id' => $comment->news->user_id,
                'type' => 'comment_on_news',
                'title' => 'Nuevo comentario en tu noticia',
                'message' => "{$comment->user->name} comentó en tu noticia: \"{$comment->content}\"",
                'data' => [
                    'comment_id' => $comment->id,
                    'news_id' => $comment->news_id,
                    'commenter_id' => $comment->user_id,
                ],
                'expires_at' => now()->addDays(7),
            ]);
        }

        // Notificar a usuarios mencionados en el comentario
        $this->notifyMentionedUsers($comment);
    }

    private function notifyMentionedUsers($comment): void
    {
        // Extraer menciones del contenido del comentario (@usuario)
        preg_match_all('/@(\w+)/', $comment->content, $matches);

        if (! empty($matches[1])) {
            $mentionedUsernames = array_unique($matches[1]);

            foreach ($mentionedUsernames as $username) {
                $user = User::where('username', $username)->first();

                if ($user && $user->id !== $comment->user_id) {
                    Notification::create([
                        'user_id' => $user->id,
                        'type' => 'mention_in_comment',
                        'title' => 'Te mencionaron en un comentario',
                        'message' => "{$comment->user->name} te mencionó en un comentario: \"{$comment->content}\"",
                        'data' => [
                            'comment_id' => $comment->id,
                            'news_id' => $comment->news_id,
                            'mentioner_id' => $comment->user_id,
                        ],
                        'expires_at' => now()->addDays(7),
                    ]);
                }
            }
        }
    }
}
