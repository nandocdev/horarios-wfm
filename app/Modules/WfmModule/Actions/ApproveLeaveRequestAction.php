<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\LeaveRequestApproval;
use App\Shared\Events\LeaveRequestDecision;
use Illuminate\Support\Facades\DB;

final class ApproveLeaveRequestAction
{
    /**
     * Aprueba una solicitud de permiso de forma transaccional.
     */
    public function execute(int $leaveId, int $approverId, int $userId, string $comment = 'Aprobado por Jefe Inmediato'): LeaveRequest
    {
        return DB::transaction(function () use ($leaveId, $approverId, $userId, $comment) {
            $leave = LeaveRequest::where('id', $leaveId)
                ->where('status', 'pending')
                ->firstOrFail();

            LeaveRequestApproval::create([
                'leave_request_id' => $leave->id,
                'approver_id' => $approverId,
                'status' => 'approved',
                'comment' => $comment,
                'step_order' => 1,
            ]);

            $leave->update(['status' => 'approved']);

            // Disparar evento de dominio
            LeaveRequestDecision::dispatch($leave, 'approved', $userId, $comment);

            return $leave;
        });
    }
}
