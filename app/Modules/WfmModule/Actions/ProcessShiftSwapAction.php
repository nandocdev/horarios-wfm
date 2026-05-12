<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WorkflowsModule\Models\ShiftSwapApproval;
use App\Modules\WorkflowsModule\Models\ShiftSwapRequest;
use App\Shared\Events\ShiftSwapApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProcessShiftSwapAction
{
    /**
     * Ejecuta el intercambio de turno de forma inmutable y validada.
     *
     * @param int $requestId
     * @param int $approverEmployeeId
     * @return bool
     * @throws \Exception
     */
    public function execute(int $requestId, int $approverEmployeeId): bool
    {
        return DB::transaction(function () use ($requestId, $approverEmployeeId) {
            // 1. Bloqueo pesimista de la solicitud
            $request = ShiftSwapRequest::where('id', $requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'accepted') {
                throw new \Exception("La solicitud no está en estado aceptado para ser procesada.");
            }

            // 2. Cargar y bloquear las asignaciones actuales
            $assignmentA = $this->getAssignmentForLock((int)$request->requester_id, $request->requested_date->toDateString());
            $assignmentB = $this->getAssignmentForLock((int)$request->recipient_id, $request->requested_date->toDateString());

            if (!$assignmentA || !$assignmentB) {
                throw new \Exception("Una o ambas asignaciones originales ya no existen o fueron modificadas.");
            }

            // 3. Validación de integridad contra Snapshots
            $this->validateAgainstSnapshot($assignmentA, $request->requester_assignment_snapshot);
            $this->validateAgainstSnapshot($assignmentB, $request->recipient_assignment_snapshot);

            // 4. Crear registro de aprobación de WFM
            ShiftSwapApproval::create([
                'shift_swap_request_id' => $request->id,
                'approver_id' => $approverEmployeeId,
                'status' => 'approved',
                'comment' => 'Aprobado y procesado por WFM (Inmutable Flow)',
            ]);

            // 5. Ejecutar intercambio inmutable
            $this->performImmutableSwap($request, $assignmentA, $assignmentB);

            // 6. Actualizar estado de la solicitud
            $request->update(['status' => 'approved']);

            // 7. Disparar evento de dominio
            event(new ShiftSwapApproved($request, $approverEmployeeId));

            return true;
        });
    }

    /**
     * Obtiene una asignación activa y la bloquea para actualización.
     */
    private function getAssignmentForLock(int $employeeId, string $date): ?WeeklyScheduleAssignment
    {
        return WeeklyScheduleAssignment::where('employee_id', $employeeId)
            ->where('is_replaced', false)
            ->whereHas('weeklySchedule', function($q) use ($date) {
                $q->where('week_start_date', '<=', $date)
                  ->where('week_end_date', '>=', $date);
            })
            ->where('day_of_week', \Carbon\Carbon::parse($date)->dayOfWeekIso)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Valida que la asignación actual sea idéntica a la capturada en el snapshot.
     */
    private function validateAgainstSnapshot(WeeklyScheduleAssignment $current, ?array $snapshot): void
    {
        if (!$snapshot) {
            return; // Si no hay snapshot, saltamos validación dura (backward compatibility)
        }

        if ($current->schedule_id !== $snapshot['schedule_id']) {
            throw new \Exception("El turno original del empleado #{$current->employee_id} ha cambiado desde que se realizó la solicitud.");
        }
    }

    /**
     * Realiza el intercambio físico sin mutar los registros antiguos.
     */
    private function performImmutableSwap(ShiftSwapRequest $request, WeeklyScheduleAssignment $a, WeeklyScheduleAssignment $b): void
    {
        $now = now();

        // Marcar antiguas como reemplazadas
        $a->update([
            'is_replaced' => true,
            'replaced_at' => $now,
        ]);

        $b->update([
            'is_replaced' => true,
            'replaced_at' => $now,
        ]);

        // Crear nuevas asignaciones invertidas
        // Nota: A toma el turno de B y B toma el turno de A
        $this->createSwappedAssignment($a, $b, $request->id);
        $this->createSwappedAssignment($b, $a, $request->id);
    }

    private function createSwappedAssignment(WeeklyScheduleAssignment $original, WeeklyScheduleAssignment $sourceData, int $requestId): void
    {
        WeeklyScheduleAssignment::create([
            'weekly_schedule_id' => $original->weekly_schedule_id,
            'employee_id' => $original->employee_id, // Mantiene al dueño
            'day_of_week' => $original->day_of_week,
            'schedule_id' => $sourceData->schedule_id, // Toma el turno del otro
            'start_time' => $sourceData->start_time,
            'end_time' => $sourceData->end_time,
            'lunch_start_time' => $sourceData->lunch_start_time,
            'lunch_end_time' => $sourceData->lunch_end_time,
            'break_start_time' => $sourceData->break_start_time,
            'break_end_time' => $sourceData->break_end_time,
            'swap_request_id' => $requestId,
            'is_replaced' => false,
        ]);
    }
}
