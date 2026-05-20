<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Notifications\ShiftSwapApprovedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyShiftSwapApproved implements ShouldQueue
{
    public function handle(ShiftSwapApproved $event): void
    {
        $request = $event->shiftSwap;

        // Notificar al solicitante
        if ($request->requester?->user) {
            $dto = new NotificationDTO(
                title: 'Cambio de Turno Aprobado',
                message: "Tu solicitud de cambio para el {$request->requested_date->format('d/m/Y')} ha sido aprobada.",
                actionUrl: route('schedules.my-schedule'),
                icon: 'check-circle',
                level: 'success'
            );

            $request->requester->user->notify(new ShiftSwapApprovedNotification($dto));
        }

        // Notificar al receptor
        if ($request->recipient?->user) {
            $dto = new NotificationDTO(
                title: 'Intercambio de Turno Confirmado',
                message: "Se ha confirmado el intercambio de turno con {$request->requester->full_name} para el {$request->requested_date->format('d/m/Y')}.",
                actionUrl: route('schedules.my-schedule'),
                icon: 'arrows-right-left',
                level: 'info'
            );

            $request->recipient->user->notify(new ShiftSwapApprovedNotification($dto));
        }
    }
}
