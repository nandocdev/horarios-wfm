<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Listeners;

use App\Modules\OperationsModule\Notifications\AdherenceAlertNotification;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\DTOs\NotificationDTO;
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

        $dto = new NotificationDTO(
            title: 'Alerta de Adherencia',
            message: "{$employee->first_name} {$employee->last_name} — {$event->label}.",
            actionUrl: route('operations.realtime-monitoring'),
            icon: 'exclamation-triangle',
            level: 'warning',
        );

        // Notify the supervisor
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
