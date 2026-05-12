<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Listeners;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\WfmModule\Events\LeaveRequestCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class AuditLeaveRequestCreatedListener implements ShouldQueue
{
    public function handle(LeaveRequestCreated $event): void
    {
        $leave = $event->leaveRequest;

        AuditLog::create([
            'entity_type' => get_class($leave),
            'entity_id' => $leave->id ?? null,
            'action' => 'leave_request.created',
            'before' => null,
            'after' => $leave->toArray() ?? null,
            'ip_address' => request()?->ip(),
            'user_id' => $leave->employee_id ?? auth()->id(),
        ]);
    }
}
