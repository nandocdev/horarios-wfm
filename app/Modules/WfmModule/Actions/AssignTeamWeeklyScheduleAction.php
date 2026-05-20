<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Team;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use App\Shared\Events\ScheduleAssignmentUpdated;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssignTeamWeeklyScheduleAction
{
    /**
     * Asigna un turno base a todo un equipo para una semana específica.
     */
    public function execute(int $weeklyScheduleId, int $teamId, int $scheduleId, ?string $lunchStart = null, ?string $breakStart = null, ?string $startTime = null, ?string $endTime = null): void
    {
        DB::transaction(function () use ($weeklyScheduleId, $teamId, $scheduleId, $lunchStart, $breakStart, $startTime, $endTime) {
            $schedule = Schedule::findOrFail($scheduleId);
            $team = Team::findOrFail($teamId);
            $employeeIds = $team->users()->pluck('employees.id');

            // Colección para evitar duplicados en notificaciones masivas
            $affectedAssignments = [];

            // Si no se pasan tiempos personalizados, usamos los del turno
            $startTime = $startTime ?: $schedule->start_time;
            $endTime = $endTime ?: $schedule->end_time;

            // Calcular tiempos de fin si se proporcionan los de inicio
            $lunchEnd = $lunchStart ? Carbon::parse($lunchStart)->addMinutes($schedule->lunch_minutes)->format('H:i:s') : null;
            $breakEnd = $breakStart ? Carbon::parse($breakStart)->addMinutes($schedule->break_minutes)->format('H:i:s') : null;

            // 1. Asignar a nivel de equipo para los 7 días
            for ($day = 1; $day <= 7; $day++) {
                $isAllowed = in_array($day, $schedule->allowed_days ?? []);

                if ($isAllowed) {
                    WeeklyTeamAssignment::updateOrCreate(
                        [
                            'weekly_schedule_id' => $weeklyScheduleId,
                            'team_id' => $teamId,
                            'day_of_week' => $day,
                        ],
                        [
                            'schedule_id' => $scheduleId,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'lunch_start_time' => $lunchStart,
                            'lunch_end_time' => $lunchEnd,
                            'break_start_time' => $breakStart,
                            'break_end_time' => $breakEnd,
                        ]
                    );

                    // Propagar a nivel individual
                    foreach ($employeeIds as $employeeId) {
                        $assignment = WeeklyScheduleAssignment::updateOrCreate(
                            [
                                'weekly_schedule_id' => $weeklyScheduleId,
                                'employee_id' => $employeeId,
                                'day_of_week' => $day,
                            ],
                            [
                                'schedule_id' => $scheduleId,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'lunch_start_time' => $lunchStart,
                                'lunch_end_time' => $lunchEnd,
                                'break_start_time' => $breakStart,
                                'break_end_time' => $breakEnd,
                            ]
                        );

                        // Agregamos a la lista de afectados para notificar al final (solo una vez por empleado)
                        if (! isset($affectedAssignments[$employeeId])) {
                            $affectedAssignments[$employeeId] = $assignment;
                        }
                    }
                } else {
                    // Si el día no está permitido en el nuevo turno, eliminamos asignaciones previas (día OFF)
                    WeeklyTeamAssignment::where([
                        'weekly_schedule_id' => $weeklyScheduleId,
                        'team_id' => $teamId,
                        'day_of_week' => $day,
                    ])->delete();

                    WeeklyScheduleAssignment::where([
                        'weekly_schedule_id' => $weeklyScheduleId,
                        'day_of_week' => $day,
                    ])->whereIn('employee_id', $employeeIds)->delete();
                }
            }

            // Notificar a los empleados afectados solo una vez por toda la acción masiva
            foreach ($affectedAssignments as $assignment) {
                ScheduleAssignmentUpdated::dispatch($assignment, auth()->id() ?? 0);
            }
        });
    }
}
