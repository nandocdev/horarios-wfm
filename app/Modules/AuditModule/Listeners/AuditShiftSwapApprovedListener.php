<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\WfmModule\Events\ShiftSwapApproved;
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
            'after' => $swap->toArray() ?? null,
            'ip_address' => request()?->ip(),
            'user_id' => $event->approverId,
        ]);
    }
}
