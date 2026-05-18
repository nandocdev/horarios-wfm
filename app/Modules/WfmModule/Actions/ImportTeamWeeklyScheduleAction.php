<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Support\Facades\DB;

class ImportTeamWeeklyScheduleAction
{
    public function execute(int $weekId, int $teamId, array $days, array $importedData): void
    {
        DB::transaction(function () use ($weekId, $teamId, $days, $importedData) {
            // Obtener los IDs de los empleados del equipo basados en el nombre de usuario o código si aplicara
            // El CSV tiene 'usuario' que en este sistema probablemente es el campo `username` o la primera parte del email
            // Pero asumiendo que 'usuario' del CSV es `username` o `code` de PersonnelModule\Models\Employee, busquémoslos.
            $usernames = array_column($importedData, 'usuario');
            $employees = Employee::whereIn('username', $usernames) // Asumiendo que Employee tiene username
                                 ->orWhereIn('email', $usernames)
                                 ->get()->keyBy('username'); // O ajustar según como se ligue

            // Pre-cargar todos los turnos base
            $allSchedules = \App\Modules\WfmModule\Models\Schedule::where('is_active', true)->get();
            $schedulesByTime = $allSchedules->keyBy(fn($s) => \Illuminate\Support\Carbon::parse($s->start_time)->format('H:i'));
            $schedulesByName = $allSchedules->keyBy(fn($s) => strtolower($s->name));
            $fallbackSchedule = $allSchedules->first();

            foreach ($importedData as $row) {
                // Buscamos el empleado. Como 'usuario' podría venir como username o parecido
                $employee = $employees->first(function($emp) use ($row) {
                    // Check against multiple fields just in case
                    return $emp->username === $row['usuario'] || 
                           explode('@', $emp->email ?? '')[0] === $row['usuario'];
                });

                if (!$employee) {
                    continue; // Skip if employee not found
                }

                // Asignar o actualizar para cada día seleccionado
                foreach ($days as $dayNum) {
                    $assignment = WeeklyScheduleAssignment::firstOrNew([
                        'weekly_schedule_id' => $weekId,
                        'employee_id' => $employee->id,
                        'day_of_week' => $dayNum,
                    ]);

                    // Determinar si es libre o tiene horario.
                    $assignment->start_time = $row['entrada'] ?: null;
                    $assignment->end_time = $row['salida'] ?: null;
                    $assignment->lunch_start_time = $row['ini_almuerzo'] ?: null;
                    $assignment->break_start_time = $row['ini_descanso'] ?: null;

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
                    
                    $assignment->save();
                }
            }
            
            // Aquí podríamos emitir un evento
        });
    }
}
