<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Shared\Events\ShiftSwapApproved;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendShiftSwapApprovedNotification implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(ShiftSwapApproved $event): void {
        $swap = $event->shiftSwap;

        if ($swap === null) {
            return;
        }

        $requesterUserId = $swap->requester_id ?? $swap->requester?->user_id ?? $swap->requested_by ?? null;
        $recipientUserId = $swap->recipient_id ?? $swap->recipient?->user_id ?? $swap->target_user_id ?? null;

        $approverId = (int) $event->approverId;
        $notifiedUserIds = [];

        if ($requesterUserId !== null && (int) $requesterUserId !== $approverId) {
            $requester = User::find((int) $requesterUserId);

            if ($requester !== null) {
                $notification = InAppNotification::create(
                    id: (string) Str::uuid(),
                    userId: (int) $requesterUserId,
                    type: 'shift_swap_approved',
                    notifiableType: get_class($swap),
                    notifiableId: (int) ($swap->id ?? 0),
                    title: 'Intercambio aprobado',
                    message: 'Tu solicitud de intercambio de turno fue aprobada.',
                    data: [
                        'swap_id' => $swap->id,
                        'status' => 'approved',
                    ],
                );

                $this->notificationRepository->save($notification);

                $this->broadcastChannel->send(
                    notifiable: $requester,
                    title: 'Intercambio aprobado',
                    message: 'Tu solicitud de intercambio de turno fue aprobada.',
                    level: 'success',
                    icon: 'check-circle',
                    actionUrl: '/wfm/shift-swaps',
                );

                $notifiedUserIds[] = (int) $requesterUserId;
            }
        }

        if ($recipientUserId !== null && (int) $recipientUserId !== $approverId && !in_array((int) $recipientUserId, $notifiedUserIds, true)) {
            $recipient = User::find((int) $recipientUserId);

            if ($recipient !== null) {
                $notification = InAppNotification::create(
                    id: (string) Str::uuid(),
                    userId: (int) $recipientUserId,
                    type: 'shift_swap_approved',
                    notifiableType: get_class($swap),
                    notifiableId: (int) ($swap->id ?? 0),
                    title: 'Intercambio aprobado',
                    message: 'El intercambio de turno en el que participas fue aprobado.',
                    data: [
                        'swap_id' => $swap->id,
                        'status' => 'approved',
                    ],
                );

                $this->notificationRepository->save($notification);

                $this->broadcastChannel->send(
                    notifiable: $recipient,
                    title: 'Intercambio aprobado',
                    message: 'El intercambio de turno en el que participas fue aprobado.',
                    level: 'success',
                    icon: 'check-circle',
                    actionUrl: '/wfm/shift-swaps',
                );
            }
        }
    }
}
