<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Events\ReactionAdded;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use App\Src\Platform\Infrastructure\Persistence\EloquentShoutout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendReactionNotificationListener implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(ReactionAdded $event): void {
        $reaction = $event->reaction;
        $shoutout = EloquentShoutout::find($reaction->shoutoutId());

        if ($shoutout === null) {
            return;
        }

        $creatorId = (int) $shoutout->employee_id;
        $reactorId = $reaction->userId();

        if ($creatorId === $reactorId) {
            return;
        }

        $creator = User::find($creatorId);

        if ($creator === null) {
            return;
        }

        $reactionEmoji = $reaction->type()->emoji();

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: $creatorId,
            type: 'reaction',
            notifiableType: get_class($shoutout),
            notifiableId: (int) $shoutout->id,
            title: 'Reacción recibida',
            message: "Alguien reaccionó a tu shoutout con {$reactionEmoji}.",
            data: [
                'reaction_id' => $reaction->id(),
                'shoutout_id' => $reaction->shoutoutId(),
                'reaction_type' => $reaction->type()->value,
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $creator,
            title: 'Reacción recibida',
            message: "Alguien reaccionó a tu shoutout con {$reactionEmoji}.",
            level: 'info',
            icon: 'emoji-happy',
            actionUrl: "/platform/communications/shoutouts/{$reaction->shoutoutId()}",
        );
    }
}
