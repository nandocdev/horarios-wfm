<?php

declare(strict_types=1);

namespace App\Src\Platform\Infrastructure\Listeners;

use App\Shared\Events\LeaveRequestDecision;
use App\Src\Platform\Infrastructure\Persistence\AuditLogBridge;
use Illuminate\Contracts\Queue\ShouldQueue;

final class AuditLeaveRequestDecisionListener implements ShouldQueue {
    public function handle(LeaveRequestDecision $event): void {
        $leave = $event->leaveRequest;

        AuditLogBridge::logCustom(
            entityType: get_class($leave),
            entityId: $leave?->id,
            action: 'leave_request.' . $event->status,
            before: null,
            after: array_merge($leave?->toArray() ?? [], [
                'decision' => $event->status,
                'reason' => $event->reason,
            ]),
            ipAddress: null,
            userId: $event->decidedByUserId,
        );
    }
}
