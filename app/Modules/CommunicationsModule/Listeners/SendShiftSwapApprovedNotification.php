<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\ShiftSwapApprovedNotification;
use App\Modules\WfmModule\Events\ShiftSwapApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
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
        ];

        // Notify both parties if emails exist
        foreach (['employee_id_from', 'employee_id_to'] as $field) {
            if (! empty($swap->{$field})) {
                $email = DB::table('users')->where('id', $swap->{$field})->value('email');
                if (! empty($email)) {
                    Notification::route('mail', $email)
                        ->notify(new ShiftSwapApprovedNotification($payload));
                }
            }
        }
    }
}
