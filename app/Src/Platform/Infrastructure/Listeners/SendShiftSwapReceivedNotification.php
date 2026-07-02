<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Shared\Events\ShiftSwapRequested;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendShiftSwapReceivedNotification implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(ShiftSwapRequested $event): void {
        $swap = $event->shiftSwap;

        if ($swap === null) {
            return;
        }

        $recipientUserId = $swap->recipient_id
            ?? $swap->recipient?->user_id
            ?? $swap->target_user_id
            ?? null;

        if ($recipientUserId === null) {
            return;
        }

        $recipient = User::find((int) $recipientUserId);

        if ($recipient === null) {
            return;
        }

        $requesterName = $swap->requester?->name
            ?? $swap->requester?->user?->name
            ?? 'Un compañero';

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: (int) $recipientUserId,
            type: 'shift_swap_requested',
            notifiableType: get_class($swap),
            notifiableId: (int) ($swap->id ?? 0),
            title: 'Solicitud de intercambio',
            message: "{$requesterName} quiere intercambiar turno contigo.",
            data: [
                'swap_id' => $swap->id,
                'requester_id' => $swap->requester_id ?? $swap->requested_by,
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $recipient,
            title: 'Solicitud de intercambio',
            message: "{$requesterName} quiere intercambiar turno contigo.",
            level: 'info',
            icon: 'arrows-repeat',
            actionUrl: '/wfm/shift-swaps',
        );
    }
}
