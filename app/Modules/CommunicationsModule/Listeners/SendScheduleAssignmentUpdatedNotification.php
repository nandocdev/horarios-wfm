<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Listeners;

use App\Modules\CommunicationsModule\Notifications\ScheduleAssignmentUpdatedNotification;
use App\Modules\CoreModule\Models\User;
use App\Shared\Events\ScheduleAssignmentUpdated;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendScheduleAssignmentUpdatedNotification implements ShouldQueue
{
    /**
     * Maneja el envío de notificaciones cuando se modifica un día de horario ya publicado.
     */
    public function handle(ScheduleAssignmentUpdated $event): void
    {
        $assignment = $event->assignment;

        // Asegurar que las relaciones necesarias estén cargadas
        $assignment->loadMissing(['weeklySchedule', 'employee.team', 'schedule']);

        $weeklySchedule = $assignment->weeklySchedule;

        if (! $weeklySchedule || $weeklySchedule->status !== 'published') {
            return;
        }

        // Validar que la fecha sea accesible
        if (! $assignment->date) {
            Log::warning("No se pudo calcular la fecha para la asignación {$assignment->id}");

            return;
        }

        $payload = [
            'type' => 'schedule.assignment_updated',
            'assignment_id' => $assignment->id,
            'date' => $assignment->date->format('Y-m-d'),
            'date_human' => $assignment->date->translatedFormat('l d \d\e F'),
            'start_time' => $assignment->start_time instanceof CarbonInterface ? $assignment->start_time->format('H:i') : 'N/A',
            'end_time' => $assignment->end_time instanceof CarbonInterface ? $assignment->end_time->format('H:i') : 'N/A',
            'lunch' => $assignment->lunch_start_time instanceof CarbonInterface ? $assignment->lunch_start_time->format('H:i').' - '.$assignment->lunch_end_time->format('H:i') : 'No programado',
            'break' => $assignment->break_start_time instanceof CarbonInterface ? $assignment->break_start_time->format('H:i').' - '.$assignment->break_end_time->format('H:i') : 'No programado',
            'week_period' => $weeklySchedule->week_start_date->format('d/m/Y').' al '.$weeklySchedule->week_end_date->format('d/m/Y'),
            'updated_by' => $event->updatedByUserId,
            'action_url' => url("/schedules/my-schedule?week={$weeklySchedule->id}&day={$assignment->day_of_week}"),
            'title' => 'Horario Actualizado',
            'message' => "Se ha modificado tu turno para el día {$assignment->date->format('d/m/Y')}.",
            'level' => 'warning',
        ];

        try {
            // 1. Notificar al empleado afectado (Agente)
            $agent = User::whereHas('employee', function ($query) use ($assignment) {
                $query->where('id', $assignment->employee_id);
            })->active()->first();

            if ($agent) {
                $agent->notify(new ScheduleAssignmentUpdatedNotification($payload));
            }

            // 2. Notificar a los coordinadores y supervisores involucrados
            // Obtenemos el equipo del empleado para encontrar a su supervisor
            $employee = $assignment->employee;
            if ($employee && $employee->team && ! empty($employee->team->supervisor_id)) {
                $supervisor = User::whereHas('employee', function ($query) use ($employee) {
                    $query->where('id', $employee->team->supervisor_id);
                })->active()->first();

                if ($supervisor && (! $agent || $supervisor->id !== $agent->id)) {
                    $supervisor->notify(new ScheduleAssignmentUpdatedNotification($payload));
                }
            }

        } catch (\Throwable $e) {
            Log::error('Error al enviar notificaciones de modificación de horario: '.$e->getMessage());
        }
    }
}
