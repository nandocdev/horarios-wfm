<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\OperationsModule;

use App\Modules\OperationsModule\Livewire\ControlTower\CoverageMatrixWidget;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    Livewire::withoutLazyLoading();

    DB::table('directorates')->insert([
        'id' => 1,
        'name' => 'Dirección Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('departments')->insert([
        'id' => 1,
        'directorate_id' => 1,
        'name' => 'Departamento Test',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach ([1 => 'P00001', 2 => 'P00002', 5 => 'P00005'] as $id => $code) {
        DB::table('positions')->insert([
            'id' => $id,
            'department_id' => 1,
            'name' => 'Posición '.$id,
            'position_code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $this->today = Carbon::today();
    $this->dayOfWeek = $this->today->dayOfWeekIso;

    $weekStart = $this->today->copy()->startOfWeek();
    DB::table('weekly_schedules')->insert([
        'week_start_date' => $weekStart->toDateString(),
        'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => 'published',
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->weeklyScheduleId = (int) DB::getPdo()->lastInsertId();

    $this->schedule = Schedule::factory()->create([
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
});

function coverage_assign(int $wsId, int $scheduleId, int $employeeId, int $dayOfWeek): void
{
    DB::table('weekly_schedule_assignments')->insert([
        'weekly_schedule_id' => $wsId,
        'employee_id' => $employeeId,
        'schedule_id' => $scheduleId,
        'day_of_week' => $dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_replaced' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('counts only operator positions as base, once per agent', function () {
    $operator1 = Employee::factory()->create(['position_id' => 1]);
    $operator2 = Employee::factory()->create(['position_id' => 2]);
    // Coordinador con turno asignado: NO es agente, no suma a la Base.
    $coordinator = Employee::factory()->create(['position_id' => 5]);

    coverage_assign($this->weeklyScheduleId, $this->schedule->id, $operator1->id, $this->dayOfWeek);
    coverage_assign($this->weeklyScheduleId, $this->schedule->id, $operator2->id, $this->dayOfWeek);
    coverage_assign($this->weeklyScheduleId, $this->schedule->id, $coordinator->id, $this->dayOfWeek);

    // Fila duplicada del operador 1 desde un SEGUNDO plan que también cubre hoy
    // (la BD solo impide duplicados dentro del mismo plan): debe contarse una
    // sola vez para que la Base no supere la plantilla.
    // El inicio no puede coincidir con el lunes de la semana actual (índice
    // parcial weekly_schedules_published_unique); si hoy es lunes se usa hoy.
    $overlappingStart = $this->today->copy()->subDay();
    if ($overlappingStart->eq($this->today->copy()->startOfWeek())) {
        $overlappingStart = $this->today->copy();
    }
    DB::table('weekly_schedules')->insert([
        'week_start_date' => $overlappingStart->toDateString(),
        'week_end_date' => $overlappingStart->copy()->addDays(6)->toDateString(),
        'status' => 'published',
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $wsId2 = (int) DB::getPdo()->lastInsertId();
    coverage_assign($wsId2, $this->schedule->id, $operator1->id, $this->dayOfWeek);

    $component = Livewire::test(CoverageMatrixWidget::class, [
        'selectedDate' => $this->today->toDateString(),
        'employeeIds' => [$operator1->id, $operator2->id, $coordinator->id],
    ]);

    $component->assertStatus(200)
        ->assertViewHas('totalScheduled', 20)   // 2 agentes x 10 intervalos (08:00-17:00)
        ->assertViewHas('rows', function ($rows): bool {
            $rows = $rows instanceof Collection ? $rows->all() : $rows;
            foreach ($rows as $row) {
                if ($row['programmed_raw'] > 2) {
                    return false;
                }
            }

            return count($rows) === 12
                && collect($rows)->firstWhere('hour', '08:00')['programmed_raw'] === 2
                && collect($rows)->firstWhere('hour', '08:00')['req'] === 2
                && collect($rows)->firstWhere('hour', '07:00')['programmed_raw'] === 0;
        });
});

it('shows zero base when no agents are scheduled', function () {
    Livewire::test(CoverageMatrixWidget::class, ['selectedDate' => $this->today->toDateString()])
        ->assertStatus(200)
        ->assertViewHas('totalScheduled', 0)
        ->assertViewHas('rows.0.programmed_raw', 0);
});
