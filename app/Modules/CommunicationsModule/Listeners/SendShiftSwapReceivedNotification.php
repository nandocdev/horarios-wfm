<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\ShiftSwapReceivedNotification;
use App\Modules\CoreModule\Models\User;
use App\Shared\Events\ShiftSwapRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendShiftSwapReceivedNotification implements ShouldQueue
{
    public function handle(ShiftSwapRequested $event): void
    {
        $swap = $event->shiftSwap;

        $payload = [
            'type' => 'shift_swap.requested',
            'shift_swap_id' => $swap->id ?? null,
            'employee_id_from' => $swap->employee_id_from ?? null,
            'employee_id_to' => $swap->employee_id_to ?? null,
            'date' => $swap->date ?? null,
            'title' => 'Nueva Solicitud de Intercambio',
            'message' => 'Has recibido una nueva solicitud de intercambio de turno de un compañero.',
            'level' => 'info',
        ];

        // Notify the recipient (employee_id_to) via email if available
        if (! empty($swap->employee_id_to)) {
            $email = User::where('id', $swap->employee_id_to)->value('email');
            if (! empty($email)) {
                Notification::route('mail', $email)
                    ->notify(new ShiftSwapReceivedNotification($payload));
            }
        }
    }
}
