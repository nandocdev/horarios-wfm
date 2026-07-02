<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Shared\Events\ShiftSwapApproved;
use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AuditShiftSwapApprovedListener implements ShouldQueue {
    public function handle(ShiftSwapApproved $event): void {
        $swap = $event->shiftSwap;

        AuditLogBridge::logCustom(
            entityType: get_class($swap),
            entityId: $swap?->id,
            action: 'shift_swap.approved',
            before: null,
            after: $swap?->toArray(),
            ipAddress: null,
            userId: $event->approverId,
        );
    }
}
