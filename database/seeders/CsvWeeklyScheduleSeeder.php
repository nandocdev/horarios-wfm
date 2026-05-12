<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Models\WeeklyTeamAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CsvWeeklyScheduleSeeder extends Seeder {
    public function run(): void {
        WeeklySchedule::truncate();
        WeeklyScheduleAssignment::truncate();
        WeeklyTeamAssignment::truncate();

        $csvPath = database_path('data/horario.csv');
        if (!file_exists($csvPath)) {
            $this->command->error("Archivo no encontrado: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        fgetcsv($handle); // Descartar cabecera

        // Pre-cargar todos los empleados: username → [id, team_id]
        $employees = DB::table('employees')
            ->select('id', 'username', 'team_id')
            ->get()
            ->mapWithKeys(fn($e) => [strtolower($e->username) => ['id' => $e->id, 'team_id' => $e->team_id]])
            ->toArray();

        // Pre-cargar turnos: hora_entrada → schedule
        $schedules = Schedule::all()
            ->keyBy(fn($s) => Carbon::parse($s->start_time)->format('H:i'))
            ->toArray();

        // Pre-cargar líderes por equipo: team_id → [ids de lideres]
        $teamLeaders = [];
        $teams = DB::table('teams')->select('id', 'supervisor_id')->get();
        foreach ($teams as $t) {
            $leaders = [];
            if ($t->supervisor_id) {
                $leaders[] = $t->supervisor_id;
            }

            // Los coordinadores son aquellos que son parent_id en employees O tienen el cargo específico
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

        $weeks = [];
        $count = 0;
        $skipped = 0;
        $batchSize = 250;
        $insertData = [];
        $processedKeys = []; // Registro global para evitar duplicados entre lotes

        DB::transaction(function () use ($handle, &$weeks, $employees, $schedules, &$count, &$skipped, $batchSize, &$insertData, $teamLeaders, &$processedKeys) {
            while (($row = fgetcsv($handle)) !== false) {
                // Descartar filas con columnas insuficientes
                if (count($row) < 8) {
                    $skipped++;

                    continue;
                }

                // [0]semana, [1]ini_semana, [2]fecha, [3]usuario, [4]jornada, [5]entrada, [6]almuerzo, [7]descanso
                [$weekId, $iniSemana, $fecha, $username, $jornada, $entrada, $almuerzo, $descanso] = $row;

                // 1. Asegurar Semana
                if (!isset($weeks[$iniSemana])) {
                    $startDate = Carbon::createFromFormat('d/m/Y', $iniSemana)->startOfDay();
                    $endDate = $startDate->copy()->addDays(6)->endOfDay();

                    $week = WeeklySchedule::updateOrCreate(
                        ['week_start_date' => $startDate->format('Y-m-d')],
                        [
                            'week_end_date' => $endDate->format('Y-m-d'),
                            'status' => 'draft',
                            'published_at' => now(),
                        ]
                    );
                    $weeks[$iniSemana] = $week->id;
                }

                // 2. Buscar Empleado
                $key = strtolower($username);
                $employee = $employees[$key] ?? null;
                if (!$employee) {
                    $skipped++;

                    continue;
                }

                // 3. Buscar Turno por hora de entrada
                $entradaFormateada = Carbon::parse($entrada)->format('H:i');
                $schedule = $schedules[$entradaFormateada] ?? null;
                if (!$schedule) {
                    $skipped++;

                    continue;
                }

                // 4. Calcular día de la semana ISO
                $fechaCarbon = Carbon::createFromFormat('d/m/Y', $fecha);
                $dayOfWeek = $fechaCarbon->dayOfWeekIso;

                // 5. Acumular en el batch de asignaciones individuales
                $assignKey = $weeks[$iniSemana] . '-' . $employee['id'] . '-' . $dayOfWeek;
                if (!isset($processedKeys[$assignKey])) {
                    $insertData[$assignKey] = [
                        'weekly_schedule_id' => $weeks[$iniSemana],
                        'employee_id' => $employee['id'],
                        'schedule_id' => $schedule['id'],
                        'day_of_week' => $dayOfWeek,
                        'start_time' => Carbon::parse($entrada)->format('H:i:s'),
                        'end_time' => Carbon::parse($entrada)->addHours(8)->format('H:i:s'),
                        'lunch_start_time' => Carbon::parse($almuerzo)->format('H:i:s'),
                        'lunch_end_time' => Carbon::parse($almuerzo)->addMinutes(45)->format('H:i:s'),
                        'break_start_time' => Carbon::parse($descanso)->format('H:i:s'),
                        'break_end_time' => Carbon::parse($descanso)->addMinutes(15)->format('H:i:s'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $processedKeys[$assignKey] = true;
                }

                // 6. Asignación Maestra de Equipo y LÍDERES
                $teamId = $employee['team_id'] ?? null;
                if ($teamId) {
                    $assignmentParams = [
                        'schedule_id' => $schedule['id'],
                        'start_time' => Carbon::parse($entrada)->format('H:i:s'),
                        'end_time' => Carbon::parse($entrada)->addHours(9)->format('H:i:s'),
                        'lunch_start_time' => Carbon::parse($almuerzo)->format('H:i:s'),
                        'break_start_time' => Carbon::parse($descanso)->format('H:i:s'),
                    ];

                    WeeklyTeamAssignment::updateOrCreate(
                        [
                            'weekly_schedule_id' => $weeks[$iniSemana],
                            'team_id' => $teamId,
                            'day_of_week' => $dayOfWeek,
                        ],
                        $assignmentParams
                    );

                    // Replicar horario a los líderes del equipo que no estén en el batch actual
                    foreach ($teamLeaders[$teamId] ?? [] as $leaderId) {
                        $leaderKey = $weeks[$iniSemana] . '-' . $leaderId . '-' . $dayOfWeek;
                        // Solo lo agregamos si no ha sido procesado (agentes tienen prioridad)
                        if (!isset($processedKeys[$leaderKey])) {
                            $insertData[$leaderKey] = array_merge($assignmentParams, [
                                'weekly_schedule_id' => $weeks[$iniSemana],
                                'employee_id' => $leaderId,
                                'day_of_week' => $dayOfWeek,
                                'lunch_end_time' => Carbon::parse($almuerzo)->addMinutes(45)->format('H:i:s'),
                                'break_end_time' => Carbon::parse($descanso)->addMinutes(15)->format('H:i:s'),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $processedKeys[$leaderKey] = true;
                        }
                    }
                }

                $count++;

                // Flush del batch
                if (count($insertData) >= $batchSize) {
                    $data = array_values($insertData);
                    WeeklyScheduleAssignment::insert($data);
                    $insertData = [];
                }
            }

            // Flush del remanente
            if (!empty($insertData)) {
                $data = array_values($insertData);
                WeeklyScheduleAssignment::insert($data);
            }
        });

        fclose($handle);
        $this->command->info("Carga completa: {$count} asignaciones insertadas (incluyendo líderes), {$skipped} filas saltadas.");
    }
}
