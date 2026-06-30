<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditLeaveRequestDecisionListener implements ShouldQueue
{
    public function handle(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;

        AuditLog::create([
            'entity_type' => get_class($leave),
            'entity_id' => $leave->id ?? null,
            'action' => 'leave_request.'.$event->status,
            'before' => null,
            'after' => array_merge($leave?->toArray() ?? [], [
                'decision' => $event->status,
                'reason' => $event->reason,
            ]),
            'ip_address' => null,
            'user_id' => $event->decidedByUserId,
        ]);
    }
}
