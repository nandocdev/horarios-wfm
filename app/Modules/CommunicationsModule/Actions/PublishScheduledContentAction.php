<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\News;
use Illuminate\Support\Facades\DB;

/**
 * Publica contenido programado en la fecha/hora definida por scheduled_at.
 */
class PublishScheduledContentAction
{
    /**
     * @return array{news:int}
     */
    public function execute(): array
    {
        return DB::transaction(function (): array {
            $now = now();

            $publishedNews = News::query()
                ->where('status', 'published')
                ->whereNotNull('scheduled_at')
                ->where('scheduled_at', '<=', $now)
                ->where(function ($query): void {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '>', now());
                })
                ->update([
                    'published_at' => $now,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);

            return [
                'news' => $publishedNews,
            ];
        });
    }
}
