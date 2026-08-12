<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Notifications\ScheduleModifiedNotification;
use App\Modules\WfmModule\Notifications\SchedulePublishedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use App\Shared\Events\ScheduleAssignmentUpdated;
use App\Shared\Events\WeeklySchedulePublished;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendScheduleNotification implements ShouldQueue
{
    public function handleWeeklySchedulePublished(WeeklySchedulePublished $event): void
    {
        $schedule = $event->weeklySchedule;
        $schedule->loadMissing('assignments.employee.user');

        $dto = new NotificationDTO(
            title: 'Nuevo Horario Publicado',
            message: "Se ha publicado el horario para la semana del {$schedule->week_start_date->format('d/m/Y')}.",
            summary: "Horario de la semana del {$schedule->week_start_date->format('d/m/Y')} disponible.",
            actionUrl: route('schedules.my-schedule', [], false),
            icon: 'calendar',
            level: 'info',
            notificationType: NotificationType::SchedulePublished->value,
            facts: [
                ['label' => 'Semana', 'value' => $schedule->week_start_date->format('d/m/Y')],
            ],
            resourceType: 'weekly_schedule',
            resourceId: (string) $schedule->id,
        );

        foreach ($schedule->assignments as $assignment) {
            if ($assignment->employee?->user) {
                $assignment->employee->user->notify(new SchedulePublishedNotification($dto));
            }
        }
    }

    public function handleScheduleAssignmentUpdated(ScheduleAssignmentUpdated $event): void
    {
        $assignment = $event->assignment;
        $assignment->loadMissing(['employee.user', 'weeklySchedule']);

        $employee = $assignment->employee;
        $schedule = $assignment->weeklySchedule;

        if (! $employee?->user) {
            return;
        }

        $dto = new NotificationDTO(
            title: 'Horario Modificado',
            message: "Tu horario para la semana del {$schedule->week_start_date->format('d/m/Y')} ha sido modificado.",
            summary: 'Revisa tu horario actualizado.',
            actionUrl: route('schedules.my-schedule', [], false),
            icon: 'calendar',
            level: 'warning',
            notificationType: NotificationType::ScheduleUpdated->value,
            facts: [
                ['label' => 'Semana', 'value' => $schedule->week_start_date->format('d/m/Y')],
            ],
            recommendation: 'Por favor, revisa tu horario para ver los cambios recientes.',
            resourceType: 'weekly_schedule_assignment',
            resourceId: (string) $assignment->id,
        );

        $employee->user->notify(new ScheduleModifiedNotification($dto));
    }
}
