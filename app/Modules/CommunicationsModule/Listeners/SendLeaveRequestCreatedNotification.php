<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\LeaveRequestCreatedNotification;
use App\Modules\CoreModule\Models\User;
use App\Shared\Events\LeaveRequestCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

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
            'title' => 'Nueva Solicitud de Ausencia',
            'message' => 'Un miembro de tu equipo ha solicitado un permiso o vacaciones.',
            'level' => 'info',
        ];

        if (! empty($leave->supervisor_id)) {
            $supervisor = User::find($leave->supervisor_id);
            if ($supervisor) {
                $supervisor->notify(new LeaveRequestCreatedNotification($payload));
            }
        }
    }
}
