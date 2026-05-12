<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\LeaveRequestCreatedNotification;
use App\Modules\WfmModule\Events\LeaveRequestCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendLeaveRequestCreatedNotification implements ShouldQueue
{
    public function handle(LeaveRequestCreated $event): void
    {
        // Notify the supervisor(s) or responsible users. For now, attempt to notify the creator's supervisor if present.
        $leave = $event->leaveRequest;

        $payload = [
            'type' => 'leave_request.created',
            'leave_request_id' => $leave->id ?? null,
            'employee_id' => $leave->employee_id ?? null,
            'start_date' => $leave->start_date ?? null,
            'end_date' => $leave->end_date ?? null,
        ];

        if (! empty($leave->supervisor_id)) {
            $supervisorEmail = DB::table('users')->where('id', $leave->supervisor_id)->value('email');
            if (! empty($supervisorEmail)) {
                Notification::route('mail', $supervisorEmail)
                    ->notify(new LeaveRequestCreatedNotification($payload));
            }
        }
    }
}
