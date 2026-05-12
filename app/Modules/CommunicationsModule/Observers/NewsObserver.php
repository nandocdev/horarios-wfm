<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Observers;

use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Support\Facades\Cache;

/**
 * Observador para el modelo News.
 * Maneja efectos secundarios del ciclo de vida.
 */
class NewsObserver
{
    public function created(News $news): void
    {
        $this->clearNewsCache();
    }

    public function updated(News $news): void
    {
        $this->clearNewsCache();
        Cache::forget("news:{$news->id}");
    }

    public function deleted(News $news): void
    {
        $this->clearNewsCache();
        Cache::forget("news:{$news->id}");
    }

    private function clearNewsCache(): void
    {
        Cache::forget('news_list');
        Cache::forget('news_featured');
        Cache::forget('news_recent');
    }
}
