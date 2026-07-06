<?php

declare(strict_types=1);

namespace App\Modules\WorkflowsModule\Actions;

use App\Modules\WorkflowsModule\DTOs\CreateLeaveRequestDTO;
use App\Modules\WorkflowsModule\Models\LeaveRequest;
use App\Shared\Events\LeaveRequestCreated;
use Illuminate\Support\Facades\DB;

final class CreateLeaveRequestAction
{
    /**
     * Crea una nueva solicitud de permiso de forma transaccional.
     */
    public function execute(CreateLeaveRequestDTO $dto, int $userId): LeaveRequest
    {
        return DB::transaction(function () use ($dto, $userId) {
            $leave = LeaveRequest::create([
                'employee_id' => $dto->employeeId,
                'type' => $dto->type,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
                'minutes' => $dto->minutes,
                'status' => 'pending',
                'reason' => $dto->reason,
            ]);

            // Disparar evento de dominio
            LeaveRequestCreated::dispatch($leave, $userId);

            return $leave;
        });
    }
}
