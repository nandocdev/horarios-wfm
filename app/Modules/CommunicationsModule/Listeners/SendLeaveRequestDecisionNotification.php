<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\LeaveRequestDecisionNotification;
use App\Modules\WfmModule\Events\LeaveRequestDecision;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendLeaveRequestDecisionNotification implements ShouldQueue
{
    public function handle(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;

        $payload = [
            'type' => 'leave_request.decision',
            'leave_request_id' => $leave->id ?? null,
            'decision' => $event->decision,
            'approver_id' => $event->approverId,
        ];

        // Notify the employee who created the leave request
        if (! empty($leave->employee_id)) {
            $email = DB::table('users')->where('id', $leave->employee_id)->value('email');
            if (! empty($email)) {
                Notification::route('mail', $email)
                    ->notify(new LeaveRequestDecisionNotification($payload));
            }
        }
    }
}
