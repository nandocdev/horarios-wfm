<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Infrastructure\Listeners;

use App\Modules\AuditModule\Application\RecordAuditEntry\Command;
use App\Modules\AuditModule\Application\RecordAuditEntry\Handler;
use App\Shared\Events\LeaveRequestCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

final class LogLeaveRequestCreatedListener implements ShouldQueue
{
    public function __construct(
        private Handler $handler,
    ) {}

    public function handle(LeaveRequestCreated $event): void
    {
        $leave = $event->leaveRequest;

        $command = new Command(
            entityType: $leave !== null ? get_class($leave) : 'LeaveRequest',
            entityId: $leave?->id ?? 'unknown',
            action: 'leave_request.created',
            before: null,
            after: $leave?->toArray(),
            userId: $event->requestedByUserId,
        );

        ($this->handler)($command);
    }
}
