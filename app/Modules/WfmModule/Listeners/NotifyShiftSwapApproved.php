<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Mail\ShiftSwapApprovedMail;
use App\Modules\WfmModule\Notifications\ShiftSwapApprovedNotification;
use App\Shared\DTOs\NotificationDTO;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class NotifyShiftSwapApproved implements ShouldQueue
{
    public function handle(ShiftSwapApproved $event): void
    {
        $request = $event->shiftSwap;
        $approver = Employee::find($event->approverId);

        if (!$request || !$approver) {
            return;
        }

        // 1. Recopilar destinatarios
        $recipients = collect();

        // Solicitante y su Jefe
        if ($request->requester) {
            $recipients->push($request->requester);
            if ($request->requester->manager) {
                $recipients->push($request->requester->manager);
            }
        }

        // Receptor y su Jefe
        if ($request->recipient) {
            $recipients->push($request->recipient);
            if ($request->recipient->manager) {
                $recipients->push($request->recipient->manager);
            }
        }

        // El WFM que aprueba
        $recipients->push($approver);

        // Eliminar duplicados y asegurar que tengan email
        $recipients = $recipients->unique('id')->filter(fn($e) => !empty($e->email));

        $dateRange = $request->start_date->format('d/m/Y');
        if ($request->end_date && $request->end_date->gt($request->start_date)) {
            $dateRange .= ' al ' . $request->end_date->format('d/m/Y');
        }

        // 2. Enviar Notificaciones (App) y Correos
        foreach ($recipients as $recipient) {
            // Notificación interna (solo para usuarios del sistema)
            if ($recipient->user) {
                $dto = new NotificationDTO(
                    title: 'Cambio de Turno Aprobado',
                    message: "El cambio de turno para el periodo {$dateRange} ha sido procesado.",
                    actionUrl: route('schedules.my-schedule'),
                    icon: 'check-circle',
                    level: 'success'
                );
                $recipient->user->notify(new ShiftSwapApprovedNotification($dto));
            }

            // Enviar Correo Electrónico
            Mail::to($recipient->email)->send(new ShiftSwapApprovedMail($request, $approver, $recipient));
        }
    }
}
