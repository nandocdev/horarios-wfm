<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Events\MentionCreated;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendMentionNotificationListener implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(MentionCreated $event): void {
        $mention = $event->mention;

        $mentionedUser = User::find($mention->mentionedUserId());

        if ($mentionedUser === null) {
            return;
        }

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: $mention->mentionedUserId(),
            type: 'mention',
            notifiableType: $mention->mentionableType(),
            notifiableId: $mention->mentionableId(),
            title: 'Te mencionaron',
            message: $mention->context()
                ? "Te mencionaron: \"{$mention->context()}\""
                : 'Te mencionaron en una publicación.',
            data: [
                'mention_id' => $mention->id(),
                'mentionable_type' => $mention->mentionableType(),
                'mentionable_id' => $mention->mentionableId(),
                'mentioned_by' => $mention->mentionerUserId(),
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $mentionedUser,
            title: 'Te mencionaron',
            message: 'Alguien te mencionó en una publicación.',
            level: 'info',
            icon: 'at-symbol',
            actionUrl: '#',
        );
    }
}
