<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Listeners;

use App\Modules\AuditModule\Application\RecordAuditEntry\Command;
use App\Modules\AuditModule\Application\RecordAuditEntry\Handler;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogShiftSwapApprovedListener implements ShouldQueue
{
    public function __construct(
        private Handler $handler,
    ) {}

    public function handle(ShiftSwapApproved $event): void
    {
        $swap = $event->shiftSwap;

        $command = new Command(
            entityType: $swap !== null ? get_class($swap) : 'ShiftSwap',
            entityId: $swap?->id ?? 'unknown',
            action: 'shift_swap.approved',
            before: null,
            after: $swap?->toArray(),
            userId: $event->approverId,
        );

        ($this->handler)($command);
    }
}
