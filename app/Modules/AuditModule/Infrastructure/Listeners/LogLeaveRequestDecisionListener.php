<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Listeners;

use App\Modules\AuditModule\Application\RecordAuditEntry\Command;
use App\Modules\AuditModule\Application\RecordAuditEntry\Handler;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogLeaveRequestDecisionListener implements ShouldQueue
{
    public function __construct(
        private Handler $handler,
    ) {}

    public function handle(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;

        $after = $leave?->toArray() ?? [];
        $after['decision'] = $event->status;
        $after['reason'] = $event->reason;

        $command = new Command(
            entityType: $leave !== null ? get_class($leave) : 'LeaveRequest',
            entityId: $leave?->id ?? 'unknown',
            action: 'leave_request.' . $event->status,
            before: null,
            after: $after,
            userId: $event->decidedByUserId,
        );

        ($this->handler)($command);
    }
}
