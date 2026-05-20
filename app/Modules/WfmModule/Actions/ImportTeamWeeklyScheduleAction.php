<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportTeamWeeklyScheduleAction
{
    public function execute(int $weekId, int $teamId, array $days, array $importedData): void
    {
        DB::transaction(function () use ($weekId, $days, $importedData) {
            // Convertir nombres de usuario a minúsculas para búsqueda insensible a mayúsculas
            $usernames = array_map('strtolower', array_column($importedData, 'usuario'));

            $employees = Employee::whereIn(DB::raw('LOWER(username)'), $usernames)
                ->orWhereIn(DB::raw('LOWER(email)'), $usernames)
                ->get();

            // Pre-cargar todos los turnos base
            $allSchedules = Schedule::where('is_active', true)->get();
            $schedulesByTime = $allSchedules->keyBy(fn ($s) => Carbon::parse($s->start_time)->format('H:i'));
            $schedulesByName = $allSchedules->keyBy(fn ($s) => strtolower($s->name));
            $fallbackSchedule = $allSchedules->first();

            $matchedCount = 0;
            $affectedTeams = [];
            $processedEmployees = [];

            foreach ($importedData as $row) {
                // Buscamos el empleado de forma insensible a mayúsculas
                $employee = $employees->first(function ($emp) use ($row) {
                    $target = strtolower(trim($row['usuario']));

                    return strtolower($emp->username ?? '') === $target ||
                           strtolower(explode('@', $emp->email ?? '')[0]) === $target;
                });

                if (! $employee) {
                    continue; // Skip if employee not found
                }

                $matchedCount++;

                if ($employee->team_id) {
                    $affectedTeams[$employee->team_id] = [
                        'schedule_id' => null,
                        'row' => $row,
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
                            $entradaFormateada = Carbon::parse($row['entrada'])->format('H:i');
                            $scheduleId = $schedulesByTime->get($entradaFormateada)?->id;
                        } catch (\Exception $e) {
                        }
                    }

                    if (! $scheduleId && $row['jornada']) {
                        $scheduleId = $schedulesByName->get(strtolower($row['jornada']))?->id;
                    }

                    if (! $scheduleId) {
                        $scheduleId = $fallbackSchedule?->id;
                    }

                    $assignment->schedule_id = $scheduleId;

                    if ($employee->team_id && ! $affectedTeams[$employee->team_id]['schedule_id']) {
                        $affectedTeams[$employee->team_id]['schedule_id'] = $scheduleId;
                    }

                    $assignment->save();
                    $processedEmployees[$employee->id.'-'.$dayNum] = true;
                }
            }

            // Pre-cargar líderes por equipos afectados (supervisor + coordinadores)
            $teamLeaders = [];
            $affectedTeamIds = array_keys($affectedTeams);
            if (! empty($affectedTeamIds)) {
                $teams = DB::table('teams')->whereIn('id', $affectedTeamIds)->select('id', 'supervisor_id')->get();
                foreach ($teams as $t) {
                    $leaders = [];
                    if ($t->supervisor_id) {
                        $leaders[] = $t->supervisor_id;
                    }

                    $coordinators = DB::table('employees')
                        ->join('positions', 'employees.position_id', '=', 'positions.id')
                        ->where('employees.team_id', $t->id)
                        ->where(function ($query) {
                            $query->whereIn('employees.id', function ($sub) {
                                $sub->select('parent_id')->from('employees')->whereNotNull('parent_id');
                            })
                                ->orWhere('positions.name', 'Operador Asist. Serv. Aseg. II');
                        })
                        ->pluck('employees.id')
                        ->toArray();

                    $teamLeaders[$t->id] = array_unique(array_merge($leaders, $coordinators));
                }
            }

            // Actualizar asignaciones de equipo y replicar a líderes
            foreach ($affectedTeams as $tId => $data) {
                foreach ($days as $dayNum) {
                    $teamAssignment = WeeklyTeamAssignment::firstOrNew([
                        'weekly_schedule_id' => $weekId,
                        'team_id' => $tId,
                        'day_of_week' => (int) $dayNum,
                    ]);

                    $entrada = $data['row']['entrada'] ?: null;

                    $teamAssignment->schedule_id = $data['schedule_id'];
                    $teamAssignment->start_time = $entrada;
                    // El seeder asigna una duración total de 9 horas para equipos
                    $teamAssignment->end_time = $entrada ? Carbon::parse($entrada)->addHours(9)->format('H:i') : null;
                    $teamAssignment->lunch_start_time = $data['row']['ini_almuerzo'] ?: null;
                    $teamAssignment->lunch_end_time = $data['row']['fin_almuerzo'] ?: null;
                    $teamAssignment->break_start_time = $data['row']['ini_descanso'] ?: null;
                    $teamAssignment->break_end_time = $data['row']['fin_descanso'] ?: null;
                    $teamAssignment->save();

                    // Replicar horario a los líderes del equipo que no estén ya procesados en ese día
                    foreach ($teamLeaders[$tId] ?? [] as $leaderId) {
                        $leaderKey = $leaderId.'-'.$dayNum;
                        if (! isset($processedEmployees[$leaderKey])) {
                            $leaderAssignment = WeeklyScheduleAssignment::firstOrNew([
                                'weekly_schedule_id' => $weekId,
                                'employee_id' => $leaderId,
                                'day_of_week' => (int) $dayNum,
                            ]);

                            $leaderAssignment->schedule_id = $data['schedule_id'];
                            $leaderAssignment->start_time = $entrada;
                            // El seeder asigna 9 horas de duración para los líderes
                            $leaderAssignment->end_time = $entrada ? Carbon::parse($entrada)->addHours(9)->format('H:i') : null;
                            $leaderAssignment->lunch_start_time = $data['row']['ini_almuerzo'] ?: null;
                            $leaderAssignment->lunch_end_time = $data['row']['fin_almuerzo'] ?: null;
                            $leaderAssignment->break_start_time = $data['row']['ini_descanso'] ?: null;
                            $leaderAssignment->break_end_time = $data['row']['fin_descanso'] ?: null;
                            $leaderAssignment->save();

                            $processedEmployees[$leaderKey] = true;
                        }
                    }
                }
            }

            if ($matchedCount === 0) {
                throw new \Exception('No se encontraron empleados coincidentes en la base de datos para los usuarios provistos.');
            }
        });
    }
}
