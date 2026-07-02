<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Modules\CoreModule\Models\User;
use App\Shared\Events\ScheduleAssignmentUpdated;
use App\Src\Platform\Domain\Entities\InAppNotification;
use App\Src\Platform\Domain\Repositories\InAppNotificationRepositoryInterface;
use App\Src\Platform\Infrastructure\Notifications\BroadcastNotificationChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

final class SendScheduleAssignmentUpdatedNotification implements ShouldQueue {
    public function __construct(
        private InAppNotificationRepositoryInterface $notificationRepository,
        private BroadcastNotificationChannel $broadcastChannel,
    ) {}

    public function handle(ScheduleAssignmentUpdated $event): void {
        $assignment = $event->assignment;

        if ($assignment === null) {
            return;
        }

        $employeeId = $assignment->employee_id
            ?? $assignment->employee?->id
            ?? null;

        if ($employeeId === null) {
            return;
        }

        $employee = User::whereHas('employee', fn($q) => $q->where('id', $employeeId))->first();

        if ($employee === null) {
            return;
        }

        $notification = InAppNotification::create(
            id: (string) Str::uuid(),
            userId: (int) $employee->id,
            type: 'schedule_updated',
            notifiableType: get_class($assignment),
            notifiableId: (int) ($assignment->id ?? 0),
            title: 'Horario actualizado',
            message: 'Tu horario semanal ha sido actualizado.',
            data: [
                'assignment_id' => $assignment->id,
                'schedule_id' => $assignment->schedule_id ?? $assignment->weekly_schedule_id,
                'employee_id' => $employeeId,
                'updated_by' => $event->updatedByUserId,
            ],
        );

        $this->notificationRepository->save($notification);

        $this->broadcastChannel->send(
            notifiable: $employee,
            title: 'Horario actualizado',
            message: 'Tu horario semanal ha sido actualizado.',
            level: 'info',
            icon: 'calendar',
            actionUrl: '/wfm/schedule',
        );

        $supervisorId = $assignment->employee?->supervisor_id
            ?? $assignment->employee?->supervisor?->user_id
            ?? null;

        if ($supervisorId !== null) {
            $supervisor = User::find($supervisorId);

            if ($supervisor !== null) {
                $supervisorNotification = InAppNotification::create(
                    id: (string) Str::uuid(),
                    userId: (int) $supervisorId,
                    type: 'schedule_updated_supervisor',
                    notifiableType: get_class($assignment),
                    notifiableId: (int) ($assignment->id ?? 0),
                    title: 'Horario actualizado',
                    message: "El horario de un empleado a tu cargo ha sido actualizado.",
                    data: [
                        'assignment_id' => $assignment->id,
                        'employee_id' => $employeeId,
                    ],
                );

                $this->notificationRepository->save($supervisorNotification);

                $this->broadcastChannel->send(
                    notifiable: $supervisor,
                    title: 'Horario actualizado',
                    message: 'El horario de un empleado a tu cargo ha sido actualizado.',
                    level: 'info',
                    icon: 'calendar',
                    actionUrl: '/wfm/schedule',
                );
            }
        }
    }
}
