<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Notifications\LeaveRequestDecisionNotification;
use App\Modules\WfmModule\Notifications\LeaveRequestNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Events\LeaveRequestCreated;
use App\Shared\Events\LeaveRequestDecision;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeaveRequestNotification implements ShouldQueue
{
    public function handleLeaveRequestCreated(LeaveRequestCreated $event): void
    {
        $leave = $event->leaveRequest;
        $employee = $leave->employee;

        if (! $employee) {
            return;
        }

        $typeLabel = match ($leave->type) {
            'cuatrimestral' => 'permiso cuatrimestral',
            'compensatorio' => 'día compensatorio',
            default => 'permiso',
        };

        $startDate = $leave->start_time instanceof Carbon
            ? $leave->start_time->format('d/m/Y')
            : $leave->start_time;

        $dto = new NotificationDTO(
            title: 'Nueva Solicitud de Permiso',
            message: "{$employee->first_name} {$employee->last_name} ha solicitado un {$typeLabel} para el {$startDate}.",
            actionUrl: route('schedules.leave-history'),
            icon: 'calendar',
            level: 'info',
        );

        // Notify the employee's supervisor (manager)
        $manager = $employee->manager ?? $employee->team?->supervisor;

        if ($manager?->user) {
            $manager->user->notify(new LeaveRequestNotification($dto));
        }
    }

    public function handleLeaveRequestDecision(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;
        $status = $event->status;
        $statusLabel = $status === 'approved' ? 'aprobado' : 'rechazado';

        $startDate = $leave->start_time instanceof Carbon
            ? $leave->start_time->format('d/m/Y')
            : $leave->start_time;

        $dto = new NotificationDTO(
            title: 'Permiso '.ucfirst($statusLabel),
            message: "Tu solicitud de permiso para el {$startDate} ha sido {$statusLabel}.",
            actionUrl: route('schedules.leave-history'),
            icon: $status === 'approved' ? 'check-circle' : 'x-circle',
            level: $status === 'approved' ? 'success' : 'danger',
        );

        if ($leave->employee?->user) {
            $leave->employee->user->notify(new LeaveRequestDecisionNotification($dto));
        }
    }
}
