<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Shared\Events\LeaveRequestCreated;
use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AuditLeaveRequestCreatedListener implements ShouldQueue {
    public function handle(LeaveRequestCreated $event): void {
        $leave = $event->leaveRequest;

        AuditLogBridge::logCustom(
            entityType: get_class($leave),
            entityId: $leave?->id,
            action: 'leave_request.created',
            before: null,
            after: $leave?->toArray(),
            ipAddress: null,
            userId: $event->requestedByUserId,
        );
    }
}
