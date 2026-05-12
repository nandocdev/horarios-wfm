<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Shared\Events\ScheduleAssignmentUpdated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeDayAssignmentAction
{
    /**
     * Actualiza el detalle de asignación de un empleado para un día específico.
     */
    public function execute(int $assignmentId, array $data): WeeklyScheduleAssignment
    {
        return DB::transaction(function () use ($assignmentId, $data) {
            $assignment = WeeklyScheduleAssignment::findOrFail($assignmentId);

            $assignment->fill([
                'schedule_id' => $data['schedule_id'],
                'start_time' => $data['start_time'] ?: null,
                'end_time' => $data['end_time'] ?: null,
                'lunch_start_time' => $data['lunch_start_time'] ?: null,
                'lunch_end_time' => $this->calculateEndTime($data['lunch_start_time'], $data['lunch_minutes'] ?? 60),
                'break_start_time' => $data['break_start_time'] ?: null,
                'break_end_time' => $this->calculateEndTime($data['break_start_time'], $data['break_minutes'] ?? 30),
            ]);

            $assignment->save();

            // Notificar cambios si el horario ya está publicado
            ScheduleAssignmentUpdated::dispatch($assignment, auth()->id() ?? 0);

            return $assignment;
        });
    }

    private function calculateEndTime(?string $startTime, int $minutes): ?string
    {
        if (! $startTime) {
            return null;
        }

        return Carbon::parse($startTime)->addMinutes($minutes)->format('H:i:s');
    }
}
