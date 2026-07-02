<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Domain\Repositories\NewsRepositoryInterface;
use App\Src\Platform\Domain\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SendAutomaticNewsletterHandler
{
    public function __construct(
        private NewsRepositoryInterface $newsRepository,
        private UserRepositoryInterface $userRepository,
        private InAppNotificationRepositoryInterface $notificationRepository,
    ) {}

    public function execute(): array
    {
        $now = now();

        $publishedNews = $this->newsRepository->findPublishedToday($now);

        if (empty($publishedNews)) {
            return ['news' => 0, 'notifications' => 0];
        }

        $newsCount = count($publishedNews);
        $firstNewsId = (int) $publishedNews[0]->id();
        $headlines = implode(' · ', array_map(fn ($n) => $n->title(), array_slice($publishedNews, 0, 3)));

        $alreadyNotifiedUserIds = $this->notificationRepository->findUserIdsByTypeAndDate('newsletter_auto', $now);

        $targetUsers = $this->userRepository->findActiveExcept($alreadyNotifiedUserIds);

        if (empty($targetUsers)) {
            return ['news' => $newsCount, 'notifications' => 0];
        }

        $rows = [];

        foreach ($targetUsers as $user) {
            $rows[] = InAppNotification::create(
                userId: $user->id(),
                type: 'newsletter_auto',
                title: 'Newsletter diario',
                message: "Se publicaron {$newsCount} noticias hoy. {$headlines}",
                data: [
                    'news_ids' => array_map(fn ($n) => $n->id(), $publishedNews),
                    'news_count' => $newsCount,
                    'headlines' => array_map(fn ($n) => $n->title(), array_slice($publishedNews, 0, 5)),
                ],
                expiresAt: $now->copy()->addDays(2)->toDateTimeString(),
            );
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $notification) {
                $this->notificationRepository->save($notification);
            }
        });

        return [
            'news' => $newsCount,
            'notifications' => count($rows),
        ];
    }
}
