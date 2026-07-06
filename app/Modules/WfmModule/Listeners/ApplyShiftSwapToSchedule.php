<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Listeners;

use App\Modules\WfmModule\Actions\ProcessShiftSwapAction;
use App\Shared\Events\ShiftSwapApproved;

final class ApplyShiftSwapToSchedule
{
    public function __construct(
        private ProcessShiftSwapAction $processShiftSwapAction
    ) {}

    /**
     * Maneja el evento de aprobación de intercambio de turno.
     * Ejecuta síncronamente el intercambio físico de asignaciones.
     */
    public function handle(ShiftSwapApproved $event): void
    {
        $request = $event->shiftSwap;
        $approverId = $event->approverId;

        if (!$request) {
            return;
        }

        // Ejecutar la acción física del swap horaria
        $this->processShiftSwapAction->execute((int) $request->id, (int) $approverId);
    }
}
