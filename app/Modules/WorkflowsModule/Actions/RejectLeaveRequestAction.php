<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Modules\WorkflowsModule\Models\LeaveRequestApproval;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Support\Facades\DB;

final class RejectLeaveRequestAction
{
    /**
     * Rechaza una solicitud de permiso de forma transaccional.
     */
    public function execute(int $leaveId, int $approverId, int $userId, string $comment = 'Rechazado por Jefe Inmediato'): LeaveRequest
    {
        return DB::transaction(function () use ($leaveId, $approverId, $userId, $comment) {
            $leave = LeaveRequest::where('id', $leaveId)
                ->where('status', 'pending')
                ->firstOrFail();

            LeaveRequestApproval::create([
                'leave_request_id' => $leave->id,
                'approver_id' => $approverId,
                'status' => 'rejected',
                'comment' => $comment,
                'step_order' => 1,
            ]);

            $leave->update(['status' => 'rejected']);

            // Disparar evento de dominio
            LeaveRequestDecision::dispatch($leave, 'rejected', $userId, $comment);

            return $leave;
        });
    }
}
