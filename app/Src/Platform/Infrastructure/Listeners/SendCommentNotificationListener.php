<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Events\CommentCreated;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use App\Src\Platform\Infrastructure\Persistence\EloquentNews;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendCommentNotificationListener implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(CommentCreated $event): void {
        $comment = $event->comment;
        $news = EloquentNews::find($comment->newsId());

        if ($news === null) {
            return;
        }

        $authorId = (int) $news->author_id;
        $commenterId = $comment->userId();

        if ($authorId === $commenterId) {
            return;
        }

        $author = User::find($authorId);

        if ($author === null) {
            return;
        }

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: $authorId,
            type: 'comment',
            notifiableType: get_class($news),
            notifiableId: (int) $news->id,
            title: 'Nuevo comentario',
            message: "Alguien comentó en tu publicación: \"" . mb_substr($comment->content(), 0, 100) . "\"",
            data: [
                'comment_id' => $comment->id(),
                'news_id' => $comment->newsId(),
                'news_title' => $news->title,
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $author,
            title: 'Nuevo comentario',
            message: 'Alguien comentó en tu publicación.',
            level: 'info',
            icon: 'chat',
            actionUrl: "/platform/communications/news/{$comment->newsId()}",
        );
    }
}
