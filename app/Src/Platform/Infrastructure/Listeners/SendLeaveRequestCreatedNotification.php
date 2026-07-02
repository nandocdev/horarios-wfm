<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Shared\Events\LeaveRequestCreated;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendLeaveRequestCreatedNotification implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(LeaveRequestCreated $event): void {
        $leaveRequest = $event->leaveRequest;

        if ($leaveRequest === null) {
            return;
        }

        $supervisorId = $leaveRequest->supervisor_id
            ?? $leaveRequest->employee?->supervisor_id
            ?? $leaveRequest->employee?->supervisor?->user_id
            ?? null;

        if ($supervisorId === null) {
            return;
        }

        $supervisor = User::find($supervisorId);

        if ($supervisor === null) {
            return;
        }

        $employeeName = $leaveRequest->employee?->name
            ?? $leaveRequest->employee?->user?->name
            ?? 'Un empleado';

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: (int) $supervisorId,
            type: 'leave_request_created',
            notifiableType: get_class($leaveRequest),
            notifiableId: (int) ($leaveRequest->id ?? 0),
            title: 'Nueva solicitud de ausencia',
            message: "{$employeeName} ha solicitado una ausencia.",
            data: [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id ?? $leaveRequest->employee?->id,
                'requested_by' => $event->requestedByUserId,
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $supervisor,
            title: 'Nueva solicitud de ausencia',
            message: "{$employeeName} ha solicitado una ausencia.",
            level: 'warning',
            icon: 'calendar',
            actionUrl: "/wfm/leave-requests/{$leaveRequest->id}",
        );
    }
}
