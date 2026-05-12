<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Comment;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Comment.
 * Maneja efectos secundarios como caché y limpieza.
 */
class CommentObserver
{
    public function created(Comment $comment): void
    {
        Cache::forget("news_comments:{$comment->news_id}");
        Cache::forget('comments_recent');
    }

    public function updated(Comment $comment): void
    {
        Cache::forget("comment:{$comment->id}");
        Cache::forget("news_comments:{$comment->news_id}");
    }

    public function deleted(Comment $comment): void
    {
        Cache::forget("comment:{$comment->id}");
        Cache::forget("news_comments:{$comment->news_id}");
        Cache::forget('comments_recent');
    }
}
