<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\LeaveRequestDecisionNotification;
use App\Modules\CoreModule\Models\User;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendLeaveRequestDecisionNotification implements ShouldQueue
{
    public function handle(LeaveRequestDecision $event): void
    {
        $leave = $event->leaveRequest;
        $statusLabel = $event->decision === 'approved' ? 'Aprobada' : 'Rechazada';

        $payload = [
            'type' => 'leave_request.decision',
            'leave_request_id' => $leave->id ?? null,
            'decision' => $event->decision,
            'approver_id' => $event->approverId,
            'title' => "Solicitud de Ausencia {$statusLabel}",
            'message' => "Tu solicitud de ausencia ha sido {$statusLabel}.",
            'level' => $event->decision === 'approved' ? 'success' : 'danger',
        ];

        // Notify the employee who created the leave request
        if (! empty($leave->employee_id)) {
            $employeeUser = User::whereHas('employee', fn($q) => $q->where('id', $leave->employee_id))->first();
            if ($employeeUser) {
                $employeeUser->notify(new LeaveRequestDecisionNotification($payload));
            }
        }
    }
}
