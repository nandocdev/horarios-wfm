<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Envía un newsletter automático diario con noticias publicadas.
 */
class SendAutomaticNewsletterAction
{
    /**
     * @return array{news:int,notifications:int}
     */
    public function execute(): array
    {
        $now = now();

        $news = News::query()
            ->where('status', 'published')
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->whereDate('published_at', $now->toDateString())
            ->where(function ($query) use ($now): void {
                $query->whereNull('archive_at')
                    ->orWhere('archive_at', '>', $now);
            })
            ->orderByDesc('published_at')
            ->get(['id', 'title']);

        if ($news->isEmpty()) {
            return ['news' => 0, 'notifications' => 0];
        }

        $newsCount = $news->count();
        $firstNewsId = (int) $news->first()->id;
        $headlines = $news->take(3)->pluck('title')->implode(' · ');

        $alreadyNotifiedUserIds = Notification::query()
            ->where('type', 'newsletter_auto')
            ->whereDate('created_at', $now->toDateString())
            ->pluck('user_id')
            ->all();

        $targetUserIds = User::query()
            ->where('is_active', true)
            ->when(
                ! empty($alreadyNotifiedUserIds),
                fn ($query) => $query->whereNotIn('id', $alreadyNotifiedUserIds)
            )
            ->pluck('id');

        if ($targetUserIds->isEmpty()) {
            return ['news' => $newsCount, 'notifications' => 0];
        }

        $rows = [];

        foreach ($targetUserIds as $userId) {
            $rows[] = [
                'user_id' => $userId,
                'type' => 'newsletter_auto',
                'notifiable_type' => News::class,
                'notifiable_id' => $firstNewsId,
                'title' => 'Newsletter diario',
                'message' => "Se publicaron {$newsCount} noticias hoy. {$headlines}",
                'data' => json_encode([
                    'news_ids' => $news->pluck('id')->all(),
                    'news_count' => $newsCount,
                    'headlines' => $news->take(5)->pluck('title')->all(),
                ], JSON_THROW_ON_ERROR),
                'is_read' => false,
                'read_at' => null,
                'expires_at' => $now->copy()->addDays(2),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                Notification::query()->insert($chunk);
            }
        });

        return [
            'news' => $newsCount,
            'notifications' => count($rows),
        ];
    }
}
