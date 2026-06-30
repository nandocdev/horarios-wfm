<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditShiftSwapApprovedListener implements ShouldQueue
{
    public function handle(ShiftSwapApproved $event): void
    {
        $swap = $event->shiftSwap;

        AuditLog::create([
            'entity_type' => get_class($swap),
            'entity_id' => $swap->id ?? null,
            'action' => 'shift_swap.approved',
            'before' => null,
            'after' => $swap?->toArray(),
            'ip_address' => null,
            'user_id' => $event->approverId,
        ]);
    }
}
