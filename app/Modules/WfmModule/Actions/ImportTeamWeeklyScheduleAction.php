<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Illuminate\Support\Facades\DB;

class ImportTeamWeeklyScheduleAction
{
    public function execute(int $weekId, int $teamId, array $days, array $importedData): void
    {
        DB::transaction(function () use ($weekId, $teamId, $days, $importedData) {
            // Convertir nombres de usuario a minúsculas para búsqueda insensible a mayúsculas
            $usernames = array_map('strtolower', array_column($importedData, 'usuario'));
            
            $employees = Employee::whereIn(DB::raw('LOWER(username)'), $usernames)
                                 ->orWhereIn(DB::raw('LOWER(email)'), $usernames)
                                 ->get();

            // Pre-cargar todos los turnos base
            $allSchedules = \App\Modules\WfmModule\Models\Schedule::where('is_active', true)->get();
            $schedulesByTime = $allSchedules->keyBy(fn($s) => \Illuminate\Support\Carbon::parse($s->start_time)->format('H:i'));
            $schedulesByName = $allSchedules->keyBy(fn($s) => strtolower($s->name));
            $fallbackSchedule = $allSchedules->first();

            $matchedCount = 0;
            $affectedTeams = [];

            foreach ($importedData as $row) {
                // Buscamos el empleado de forma insensible a mayúsculas
                $employee = $employees->first(function($emp) use ($row) {
                    $target = strtolower(trim($row['usuario']));
                    return strtolower($emp->username ?? '') === $target || 
                           strtolower(explode('@', $emp->email ?? '')[0]) === $target;
                });

                if (!$employee) {
                    continue; // Skip if employee not found
                }

                $matchedCount++;
                
                if ($employee->team_id) {
                    $affectedTeams[$employee->team_id] = [
                        'schedule_id' => null,
                        'row' => $row
                    ];
                }

                // Asignar o actualizar para cada día seleccionado
                foreach ($days as $dayNum) {
                    $assignment = WeeklyScheduleAssignment::firstOrNew([
                        'weekly_schedule_id' => $weekId,
                        'employee_id' => $employee->id,
                        'day_of_week' => (int) $dayNum,
                    ]);

                    $assignment->start_time = $row['entrada'] ?: null;
                    $assignment->end_time = $row['salida'] ?: null;
                    $assignment->lunch_start_time = $row['ini_almuerzo'] ?: null;
                    $assignment->lunch_end_time = $row['fin_almuerzo'] ?: null;
                    $assignment->break_start_time = $row['ini_descanso'] ?: null;
                    $assignment->break_end_time = $row['fin_descanso'] ?: null;

                    // Encontrar el schedule_id correspondiente
                    $scheduleId = null;
                    if ($row['entrada']) {
                        try {
                            $entradaFormateada = \Illuminate\Support\Carbon::parse($row['entrada'])->format('H:i');
                            $scheduleId = $schedulesByTime->get($entradaFormateada)?->id;
                        } catch (\Exception $e) {}
                    }
                    
                    if (!$scheduleId && $row['jornada']) {
                        $scheduleId = $schedulesByName->get(strtolower($row['jornada']))?->id;
                    }
                    
                    if (!$scheduleId) {
                        $scheduleId = $fallbackSchedule?->id;
                    }

                    $assignment->schedule_id = $scheduleId;
                    
                    if ($employee->team_id && !$affectedTeams[$employee->team_id]['schedule_id']) {
                        $affectedTeams[$employee->team_id]['schedule_id'] = $scheduleId;
                    }

                    $assignment->save();
                }
            }

            // Actualizar asignaciones de equipo para que se vean en la vista de planning.teams
            foreach ($affectedTeams as $tId => $data) {
                foreach ($days as $dayNum) {
                    $teamAssignment = WeeklyTeamAssignment::firstOrNew([
                        'weekly_schedule_id' => $weekId,
                        'team_id' => $tId,
                        'day_of_week' => (int) $dayNum,
                    ]);

                    $teamAssignment->schedule_id = $data['schedule_id'];
                    $teamAssignment->start_time = $data['row']['entrada'] ?: null;
                    $teamAssignment->end_time = $data['row']['salida'] ?: null;
                    $teamAssignment->lunch_start_time = $data['row']['ini_almuerzo'] ?: null;
                    $teamAssignment->lunch_end_time = $data['row']['fin_almuerzo'] ?: null;
                    $teamAssignment->break_start_time = $data['row']['ini_descanso'] ?: null;
                    $teamAssignment->break_end_time = $data['row']['fin_descanso'] ?: null;
                    $teamAssignment->save();
                }
            }

            if ($matchedCount === 0) {
                throw new \Exception('No se encontraron empleados coincidentes en la base de datos para los usuarios provistos.');
            }
        });
    }
}
