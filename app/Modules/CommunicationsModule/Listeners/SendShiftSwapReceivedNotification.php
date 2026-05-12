<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\ShiftSwapReceivedNotification;
use App\Modules\WfmModule\Events\ShiftSwapRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
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
        ];

        // Notify the recipient (employee_id_to) via email if available
        if (! empty($swap->employee_id_to)) {
            $email = DB::table('users')->where('id', $swap->employee_id_to)->value('email');
            if (! empty($email)) {
                Notification::route('mail', $email)
                    ->notify(new ShiftSwapReceivedNotification($payload));
            }
        }
    }
}
