<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Listeners;

use App\Modules\WfmModule\Notifications\AttendanceIncidentNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use App\Shared\Events\AttendanceIncidentRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAttendanceIncidentNotification implements ShouldQueue
{
    public function handle(AttendanceIncidentRegistered $event): void
    {
        $incident = $event->incident;
        $employee = $incident->employee;

        if (! $employee?->user) {
            return;
        }

        $typeName = $incident->type?->name ?? $event->typeCode;

        $dto = new NotificationDTO(
            title: 'Incidencia registrada',
            message: "Se registró una incidencia de tipo '{$typeName}'.",
            summary: 'Se detectó una incidencia de asistencia.',
            actionUrl: route('schedules.my-schedule'),
            icon: 'exclamation-triangle',
            level: 'warning',
            notificationType: NotificationType::AttendanceIncident->value,
            facts: [
                ['label' => 'Tipo', 'value' => $typeName],
                ['label' => 'Fecha', 'value' => $incident->incident_date->format('d/m/Y')],
            ],
            recommendation: 'Si consideras que existe un error, comunícate con tu supervisor.',
            resourceType: 'attendance_incident',
            resourceId: (string) $incident->id,
        );

        $employee->user->notify(new AttendanceIncidentNotification($dto));
    }
}
