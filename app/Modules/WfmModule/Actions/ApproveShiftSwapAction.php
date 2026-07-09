<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ShiftSwapApproval;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Support\Facades\DB;

final class ApproveShiftSwapAction
{
    /**
     * Aprueba una solicitud de intercambio de turnos en WorkflowsModule.
     */
    public function execute(int $requestId, int $approverEmployeeId): ShiftSwapRequest
    {
        return DB::transaction(function () use ($requestId, $approverEmployeeId) {
            $request = ShiftSwapRequest::where('id', $requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'accepted') {
                throw new \RuntimeException('La solicitud no está en estado aceptado para ser procesada.');
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date ?: $startDate;

            // 1. Crear registro de aprobación de WFM
            ShiftSwapApproval::create([
                'shift_swap_request_id' => $request->id,
                'approver_id' => $approverEmployeeId,
                'status' => 'approved',
                'comment' => 'Aprobado por WFM (Periodo: '.$startDate->format('d/m').' - '.$endDate->format('d/m').')',
            ]);

            // 2. Actualizar estado de la solicitud
            $request->update(['status' => 'approved']);

            // 3. Disparar evento de dominio para que WfmModule aplique los cambios a la rejilla de horarios
            ShiftSwapApproved::dispatch($request, $approverEmployeeId);

            return $request;
        });
    }
}
