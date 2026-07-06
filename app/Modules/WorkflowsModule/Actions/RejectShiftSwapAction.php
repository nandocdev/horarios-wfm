<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\Models\ShiftSwapApproval;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use Illuminate\Support\Facades\DB;

final class RejectShiftSwapAction
{
    /**
     * Rechaza una solicitud de intercambio de turnos en WorkflowsModule.
     */
    public function execute(int $requestId, int $approverEmployeeId, string $reason): ShiftSwapRequest
    {
        return DB::transaction(function () use ($requestId, $approverEmployeeId, $reason) {
            $request = ShiftSwapRequest::where('id', $requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'accepted') {
                throw new \RuntimeException('La solicitud no está en estado aceptado para ser rechazada.');
            }

            // 1. Crear registro de aprobación de rechazo
            ShiftSwapApproval::create([
                'shift_swap_request_id' => $request->id,
                'approver_id' => $approverEmployeeId,
                'status' => 'rejected',
                'comment' => $reason,
            ]);

            // 2. Actualizar estado de la solicitud
            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            return $request;
        });
    }
}
