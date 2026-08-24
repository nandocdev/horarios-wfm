<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Notifications\LeaveRequestDecisionNotification;
use App\Modules\WfmModule\Notifications\LeaveRequestNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Enums\NotificationType;
use App\Shared\Events\LeaveRequestCreated;
use App\Shared\Events\LeaveRequestDecision;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeaveRequestNotification implements ShouldQueue
{
    private function typeLabel(string $type): string
    {
        return match ($type) {
            'cuatrimestral' => 'Permiso Cuatrimestral',
            'compensatorio' => 'Día Compensatorio',
            default => 'Permiso',
        };
    }

    private function dateLabel($leave): string
    {
        return $leave->start_time instanceof Carbon
            ? $leave->start_time->format('d/m/Y')
            : (string) $leave->start_time;
    }

    public function handleLeaveRequestCreated(LeaveRequestCreated $event): void
    {
        $leave = $event->leaveRequest;
        $employee = $leave->employee;

        if (! $employee) {
            return;
        }

        $type = $this->typeLabel($leave->type);
        $date = $this->dateLabel($leave);
        $employeeName = "{$employee->first_name} {$employee->last_name}";

        $dto = new NotificationDTO(
            title: 'Nueva solicitud de permiso',
            message: "{$employeeName} ha solicitado un {$type}.",
            summary: "{$employeeName} ha solicitado un {$type} para el {$date}.",
            actionUrl: route('schedules.leave-history'),
            icon: 'calendar',
            level: 'info',
            notificationType: NotificationType::LeaveRequestCreated->value,
            facts: [
                ['label' => 'Tipo', 'value' => $type],
                ['label' => 'Fecha', 'value' => $date],
                ['label' => 'Solicitante', 'value' => $employeeName],
                ['label' => 'Estado', 'value' => 'Pendiente de aprobación'],
            ],
            recommendation: 'Revisa y aprueba o rechaza la solicitud.',
            resourceType: 'leave_request',
            resourceId: (string) $leave->id,
        );

        // Manager directo (Employee); si no hay, el supervisor del equipo (User).
        $manager = $employee->manager;
        $recipient = $manager?->user ?? $employee->team?->supervisor;

        if ($recipient) {
            $recipient->notify(new LeaveRequestNotification($dto));
        }
    }

    public function handleLeaveRequestDecision(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;
        $status = $event->status;
        $isApproved = $status === 'approved';
        $date = $this->dateLabel($leave);

        $dto = new NotificationDTO(
            title: $isApproved ? 'Permiso aprobado' : 'Permiso rechazado',
            message: "Tu solicitud de permiso para el {$date} ha sido ".($isApproved ? 'aprobada' : 'rechazada').'.',
            summary: 'Tu solicitud fue '.($isApproved ? 'aprobada.' : 'rechazada.'),
            actionUrl: route('schedules.leave-history'),
            icon: $isApproved ? 'check-circle' : 'x-circle',
            level: $isApproved ? 'success' : 'danger',
            notificationType: NotificationType::LeaveRequestDecision->value,
            facts: [
                ['label' => 'Fecha', 'value' => $date],
                ['label' => 'Estado', 'value' => $isApproved ? 'Aprobado' : 'Rechazado'],
            ],
            recommendation: $isApproved
                ? 'No se requiere ninguna acción adicional.'
                : 'Puedes comunicarte con tu supervisor si tienes dudas.',
            resourceType: 'leave_request',
            resourceId: (string) $leave->id,
        );

        if ($leave->employee?->user) {
            $leave->employee->user->notify(new LeaveRequestDecisionNotification($dto));
        }
    }
}
