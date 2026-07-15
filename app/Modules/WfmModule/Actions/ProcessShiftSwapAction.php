<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use App\Modules\WfmModule\Models\TemporalAssignment;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessShiftSwapAction
{
    /**
     * Ejecuta el intercambio de turno de forma inmutable y validada.
     *
     * @throws \Exception
     */
    public function execute(int $requestId, int $approverEmployeeId): bool
    {
        return DB::transaction(function () use ($requestId) {
            // 1. Bloqueo pesimista de la solicitud
            $request = ShiftSwapRequest::where('id', $requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($request->status !== 'approved') {
                throw new \Exception('La solicitud debe estar aprobada para ser procesada en el horario.');
            }

            $startDate = $request->start_date;
            $endDate = $request->end_date ?: $startDate;
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateStr = $currentDate->toDateString();

                // 2. Cargar y bloquear las asignaciones actuales para el día actual
                $assignmentA = $this->getAssignmentForLock((int) $request->requester_id, $dateStr);
                $assignmentB = $this->getAssignmentForLock((int) $request->recipient_id, $dateStr);

                // Si no hay turnos ese día específico, saltamos (esto permite swaps de periodos con días libres)
                if ($assignmentA && $assignmentB) {
                    // 3. Validación de integridad contra Snapshots (Solo el primer día para mantener simplicidad de snapshot)
                    if ($currentDate->equalTo($startDate)) {
                        $this->validateAgainstSnapshot($assignmentA, $request->requester_assignment_snapshot);
                        $this->validateAgainstSnapshot($assignmentB, $request->recipient_assignment_snapshot);
                    }

                    // 5. Ejecutar intercambio inmutable
                    $this->performImmutableSwap($request, $assignmentA, $assignmentB);
                }

                $currentDate = $currentDate->addDay();
            }

            // 6. Crear asignaciones temporales de coordinadores cruzados
            $this->createTemporalAssignments($request, $startDate, $endDate);

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
            ->whereHas('weeklySchedule', function ($q) use ($date) {
                $q->where('week_start_date', '<=', $date)
                    ->where('week_end_date', '>=', $date);
            })
            ->where('day_of_week', Carbon::parse($date)->dayOfWeekIso)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Valida que la asignación actual sea idéntica a la capturada en el snapshot.
     */
    private function validateAgainstSnapshot(WeeklyScheduleAssignment $current, ?array $snapshot): void
    {
        if (! $snapshot) {
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

    /**
     * Crea asignaciones temporales para que cada operador reporte
     * al coordinador del otro durante el periodo del swap.
     */
    private function createTemporalAssignments(ShiftSwapRequest $request, Carbon $startDate, Carbon $endDate): void
    {
        $requester = Employee::with('team')->find($request->requester_id);
        $recipient = Employee::with('team')->find($request->recipient_id);

        if (! $requester || ! $recipient) {
            return;
        }

        // Supervisor actual de cada operador (team.supervisor_id o parent_id)
        $requesterSupervisorId = $requester->team?->supervisor_id ?? $requester->parent_id;
        $recipientSupervisorId = $recipient->team?->supervisor_id ?? $recipient->parent_id;

        // Solo crear asignaciones si ambos tienen supervisor definido
        // y son distintos (swap cruzado real)
        if (! $requesterSupervisorId || ! $recipientSupervisorId) {
            return;
        }

        if ($requesterSupervisorId === $recipientSupervisorId) {
            // Mismo supervisor, no hay swap cruzado que hacer
            return;
        }

        $dateStrStart = $startDate->toDateString();
        $dateStrEnd = $endDate->toDateString();

        // Operador1 reporta al supervisor del Operador2
        TemporalAssignment::updateOrCreate(
            [
                'employee_id' => $requester->id,
                'source_type' => 'shift_swap',
                'source_id' => $request->id,
            ],
            [
                'employee_id' => $requester->id,
                'supervisor_id' => $recipientSupervisorId,
                'start_date' => $dateStrStart,
                'end_date' => $dateStrEnd,
                'source_type' => 'shift_swap',
                'source_id' => $request->id,
            ]
        );

        // Operador2 reporta al supervisor del Operador1
        TemporalAssignment::updateOrCreate(
            [
                'employee_id' => $recipient->id,
                'source_type' => 'shift_swap',
                'source_id' => $request->id,
            ],
            [
                'employee_id' => $recipient->id,
                'supervisor_id' => $requesterSupervisorId,
                'start_date' => $dateStrStart,
                'end_date' => $dateStrEnd,
                'source_type' => 'shift_swap',
                'source_id' => $request->id,
            ]
        );
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
