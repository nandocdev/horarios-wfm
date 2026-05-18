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
                    // "Vacaciones", "Licencia", etc. en 'jornada' -> Si es libre o diferente, tal vez 'start_time' = null
                    // Pero asumiendo que solo se cambian los tiempos si existen:
                    $assignment->start_time = $row['entrada'] ?: null;
                    $assignment->end_time = $row['salida'] ?: null;
                    $assignment->lunch_start_time = $row['ini_almuerzo'] ?: null;
                    $assignment->break_start_time = $row['ini_descanso'] ?: null;
                    // También se debe limpiar si el CSV trae en blanco, pero asumiendo que el schedule_id no se toca si es custom
                    $assignment->schedule_id = null; // Custom schedule ya que no viene schedule_id, o buscar uno equivalente. Si custom, debe ser null
                    
                    $assignment->save();
                }
            }
            
            // Aquí podríamos emitir un evento
        });
    }
}
