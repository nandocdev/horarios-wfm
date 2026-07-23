<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Listeners;

use App\Modules\WfmModule\Notifications\AttendanceIncidentNotification;
use App\Shared\DTOs\NotificationDTO;
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
            title: 'Incidencia de Asistencia',
            message: "Se ha registrado una incidencia de tipo '{$typeName}' para el día {$incident->incident_date->format('d/m/Y')}.",
            actionUrl: route('schedules.my-schedule'),
            icon: 'exclamation-triangle',
            level: 'warning',
        );

        $employee->user->notify(new AttendanceIncidentNotification($dto));
    }
}
