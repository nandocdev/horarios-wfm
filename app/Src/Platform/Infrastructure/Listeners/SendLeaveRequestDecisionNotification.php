<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Shared\Events\LeaveRequestDecision;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendLeaveRequestDecisionNotification implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(LeaveRequestDecision $event): void {
        $leaveRequest = $event->leaveRequest;

        if ($leaveRequest === null) {
            return;
        }

        $employeeUserId = $leaveRequest->employee?->user_id
            ?? $leaveRequest->employee?->user?->id
            ?? $leaveRequest->user_id
            ?? null;

        if ($employeeUserId === null) {
            return;
        }

        $employee = User::find((int) $employeeUserId);

        if ($employee === null) {
            return;
        }

        $statusLabel = match ($event->status) {
            'approved' => 'aprobada',
            'rejected' => 'rechazada',
            'cancelled' => 'cancelada',
            default => $event->status,
        };

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: (int) $employeeUserId,
            type: 'leave_request_decision',
            notifiableType: get_class($leaveRequest),
            notifiableId: (int) ($leaveRequest->id ?? 0),
            title: 'Solicitud de ausencia ' . $statusLabel,
            message: "Tu solicitud de ausencia fue {$statusLabel}.",
            data: [
                'leave_request_id' => $leaveRequest->id,
                'status' => $event->status,
                'decided_by' => $event->decidedByUserId,
                'reason' => $event->reason,
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $employee,
            title: 'Solicitud de ausencia ' . $statusLabel,
            message: "Tu solicitud de ausencia fue {$statusLabel}.",
            level: $event->status === 'approved' ? 'success' : 'error',
            icon: $event->status === 'approved' ? 'check-circle' : 'x-circle',
            actionUrl: "/wfm/leave-requests/{$leaveRequest->id}",
        );
    }
}
