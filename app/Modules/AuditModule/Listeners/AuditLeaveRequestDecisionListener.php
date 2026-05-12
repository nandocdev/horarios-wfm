<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\WfmModule\Events\LeaveRequestDecision;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditLeaveRequestDecisionListener implements ShouldQueue
{
    public function handle(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;

        AuditLog::create([
            'entity_type' => get_class($leave),
            'entity_id' => $leave->id ?? null,
            'action' => 'leave_request.decision',
            'before' => null,
            'after' => array_merge($leave->toArray() ?? [], ['decision' => $event->decision]),
            'ip_address' => request()?->ip(),
            'user_id' => $event->approverId,
        ]);
    }
}
