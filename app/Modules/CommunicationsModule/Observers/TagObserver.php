<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Support\Facades\Cache;

/**
 * Observa el ciclo de vida del modelo Tag.
 * Maneja efectos secundarios como limpieza de caché.
 */
class TagObserver
{
    /**
     * Maneja el evento de creación.
     */
    public function created(Tag $tag): void
    {
        Cache::forget('tags_list');
    }

    /**
     * Maneja el evento de actualización.
     */
    public function updated(Tag $tag): void
    {
        Cache::forget("tag:{$tag->id}");
        Cache::forget('tags_list');
    }

    /**
     * Maneja el evento de eliminación.
     */
    public function deleted(Tag $tag): void
    {
        Cache::forget("tag:{$tag->id}");
        Cache::forget('tags_list');
    }

    /**
     * Maneja el evento de restauración.
     */
    public function restored(Tag $tag): void
    {
        Cache::forget('tags_list');
    }

    /**
     * Maneja el evento de eliminación permanente.
     */
    public function forceDeleted(Tag $tag): void
    {
        Cache::forget("tag:{$tag->id}");
        Cache::forget('tags_list');
    }
}
