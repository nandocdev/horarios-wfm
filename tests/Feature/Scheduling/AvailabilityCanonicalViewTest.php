<?php

declare(strict_types=1);

use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\WfmModule\Models\IntradayActivity;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('calcula la disponibilidad canónica restando pausas y actividades intradía', function () {
    // 1. Setup
    // Aseguramos que Carbon trabaje en UTC para este test
    Carbon::setTestNow(Carbon::now('UTC'));

    $employee = Employee::factory()->create();
    $schedule = Schedule::create([
        'name' => '0800-1600',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
    ]);

    $weekStart = Carbon::parse('2026-04-20', 'UTC')->startOfWeek(); // Lunes UTC
    $week = WeeklySchedule::create([
        'week_start_date' => $weekStart,
        'week_end_date' => $weekStart->copy()->endOfWeek(),
        'status' => 'published',
    ]);

    // Asignación Base con Almuerzo
    // Almuerzo: 12:00 - 12:45
    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $week->id,
        'employee_id' => $employee->id,
        'schedule_id' => $schedule->id,
        'day_of_week' => 1, // Lunes
        'lunch_start_time' => '12:00:00',
        'lunch_end_time' => '12:45:00',
    ]);

    // Actividad Intradía: 10:00 - 10:30 (Capacitación)
    $activity = IntradayActivity::create(['name' => 'Training', 'type' => 'meeting']);
    DB::table('intraday_activity_assignments')->insert([
        'intraday_activity_id' => $activity->id,
        'employee_id' => $employee->id,
        'start_at' => $weekStart->copy()->setTime(10, 0)->toDateTimeString(),
        'end_at' => $weekStart->copy()->setTime(10, 30)->toDateTimeString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 2. Ejecutar consulta a la vista
    $effective = DB::table('employee_effective_schedule')
        ->where('employee_id', $employee->id)
        ->orderBy(DB::raw('lower(time_range)'))
        ->get();

    // 3. Verificaciones
    // Esperamos 3 bloques de disponibilidad:
    // [08:00 - 10:00)
    // [10:30 - 12:00)
    // [12:45 - 16:00)

    expect($effective)->toHaveCount(3);

    // Bloque 1: 08:00 - 10:00
    expect(formatRange($effective[0]->time_range))->toBe('08:00-10:00');
    // Bloque 2: 10:30 - 12:00
    expect(formatRange($effective[1]->time_range))->toBe('10:30-12:00');
    // Bloque 3: 12:45 - 16:00
    expect(formatRange($effective[2]->time_range))->toBe('12:45-16:00');
});

/**
 * Helper para parsear tstzrange de Postgres y comparar legiblemente.
 */
function formatRange(string $range): string
{
    // Formato Postgres: ["2026-04-20 08:00:00+00","2026-04-20 10:00:00+00")
    preg_match('/\"(.*?)\",\"(.*?)\"/', $range, $matches);
    $start = Carbon::parse($matches[1])->setTimezone('UTC');
    $end = Carbon::parse($matches[2])->setTimezone('UTC');

    return $start->format('H:i').'-'.$end->format('H:i');
}
