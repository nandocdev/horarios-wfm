<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Mention;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Mention.
 * Maneja efectos secundarios como caché y limpieza.
 */
class MentionObserver
{
    public function created(Mention $mention): void
    {
        Cache::forget("user_mentions:{$mention->mentioned_user_id}");
        Cache::forget('mentions_recent');
    }

    public function updated(Mention $mention): void
    {
        Cache::forget("mention:{$mention->id}");
        Cache::forget("user_mentions:{$mention->mentioned_user_id}");
    }

    public function deleted(Mention $mention): void
    {
        Cache::forget("mention:{$mention->id}");
        Cache::forget("user_mentions:{$mention->mentioned_user_id}");
        Cache::forget('mentions_recent');
    }
}
