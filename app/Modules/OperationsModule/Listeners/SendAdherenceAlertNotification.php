<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Listeners;

use App\Modules\OperationsModule\Notifications\AdherenceAlertNotification;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use App\Shared\Events\AdherenceAlertTriggered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAdherenceAlertNotification implements ShouldQueue
{
    public function handle(AdherenceAlertTriggered $event): void
    {
        $employee = $event->employee instanceof Employee
            ? $event->employee
            : Employee::find($event->employee);

        if (! $employee) {
            return;
        }

        $employeeName = "{$employee->first_name} {$employee->last_name}";

        $dto = new NotificationDTO(
            title: 'Alerta de adherencia',
            message: "{$employeeName} — {$event->label}.",
            summary: 'Se detectó una desviación respecto al horario planificado.',
            actionUrl: route('operations.realtime-monitoring'),
            icon: 'exclamation-triangle',
            level: 'critical',
            notificationType: NotificationType::AdherenceAlert->value,
            facts: [
                ['label' => 'Empleado', 'value' => $employeeName],
                ['label' => 'Estado', 'value' => $event->label],
                ['label' => 'Duración', 'value' => $event->durationSeconds.'s'],
            ],
            recommendation: 'Se recomienda verificar la situación del agente.',
            resourceType: 'employee',
            resourceId: (string) $employee->id,
        );

        $manager = $employee->manager ?? $employee->team?->supervisor;

        if ($manager?->user) {
            $manager->user->notify(new AdherenceAlertNotification($dto));
        }

        Log::info('Adherencia alerta enviada', [
            'employee_id' => $employee->id,
            'alert_type' => $event->alertType,
            'label' => $event->label,
            'duration' => $event->durationSeconds,
        ]);
    }
}
