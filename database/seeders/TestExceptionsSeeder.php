<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduleOverride;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TestExceptionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Semana del 20 de Abril 2026 (Lunes)
        $start = Carbon::parse('2026-04-20');
        $end = $start->copy()->addDays(6);

        $week = WeeklySchedule::updateOrCreate(
            ['week_start_date' => $start->toDateString()],
            [
                'week_end_date' => $end->toDateString(),
                'status' => 'draft',
            ]
        );

        // 2. Agente de prueba
        $agent = Employee::where('is_active', true)->first();
        if (! $agent) {
            $this->command->error('No hay empleados activos para la prueba.');

            return;
        }

        $schedule = Schedule::first();
        if (! $schedule) {
            $this->command->error('No hay turnos creados.');

            return;
        }

        // 3. Crear asignaciones base para toda la semana
        for ($i = 1; $i <= 7; $i++) {
            WeeklyScheduleAssignment::updateOrCreate(
                [
                    'weekly_schedule_id' => $week->id,
                    'employee_id' => $agent->id,
                    'day_of_week' => $i,
                ],
                [
                    'schedule_id' => $schedule->id,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ]
            );
        }

        // 4. Crear una EXCEPCIÓN (Vacaciones de Miércoles a Viernes)
        // Miércoles 22 a Viernes 24 (day 3, 4, 5)

        $vacaStart = Carbon::parse('2026-04-22 00:00:00');
        $vacaEnd = Carbon::parse('2026-04-24 23:59:59');

        // Borrar anteriores para limpieza
        ScheduleOverride::where('employee_id', $agent->id)
            ->where('override_type', 'leave')
            ->delete();

        $override = new ScheduleOverride([
            'employee_id' => $agent->id,
            'override_type' => 'leave',
            'priority' => 100,
            'metadata' => ['reason' => 'Vacaciones de prueba Multi-día'],
        ]);
        $override->time_range = DB::raw("tstzrange('{$vacaStart->toDateTimeString()}', '{$vacaEnd->toDateTimeString()}', '[]')");
        $override->save();

        $this->command->info("✅ Prueba lista para el agente: {$agent->full_name}");
        $this->command->info("Semana: {$start->toDateString()}");
        $this->command->info('Vacaciones inyectadas: Miércoles 22 a Viernes 24 de Abril.');
        $this->command->warn('Navega a la planificación de la semana del 20 de Abril para ver los badges.');
    }
}
