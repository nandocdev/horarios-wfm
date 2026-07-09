<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\ShiftSwapApprovedNotification;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendShiftSwapApprovedNotification implements ShouldQueue
{
    public function handle(ShiftSwapApproved $event): void
    {
        $swap = $event->shiftSwap;

        $payload = [
            'type' => 'shift_swap.approved',
            'shift_swap_id' => $swap->id ?? null,
            'approver_id' => $event->approverId,
            'title' => 'Intercambio de Turno Aprobado',
            'message' => 'Tu solicitud de intercambio de turno ha sido aprobada por el supervisor.',
            'level' => 'success',
        ];

        // Notify both parties if emails exist
        foreach (['employee_id_from', 'employee_id_to'] as $field) {
            if (! empty($swap->{$field})) {
                $email = User::where('id', $swap->{$field})->value('email');
                if (! empty($email)) {
                    Notification::route('mail', $email)
                        ->notify(new ShiftSwapApprovedNotification($payload));
                }
            }
        }
    }
}
