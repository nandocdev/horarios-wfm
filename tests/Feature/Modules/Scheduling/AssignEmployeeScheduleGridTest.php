<?php

declare(strict_types=1);

use App\Modules\CoreModule\Models\User;
use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\SchedulingModule\Livewire\AssignGrid;
use App\Modules\SchedulingModule\Models\Schedule;
use App\Modules\SchedulingModule\Models\WeeklySchedule;
use App\Modules\SchedulingModule\Models\WeeklyScheduleAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('preflight detects overlaps and prevents apply when invalid', function () {
    if (DB::getDriverName() === 'sqlite') {
        $this->markTestSkipped('Livewire grid tests require PostgreSQL.');
    }
    $scheduleA = Schedule::create(['name' => 'A', 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'is_active' => true]);
    $scheduleB = Schedule::create(['name' => 'B', 'start_time' => '11:00:00', 'end_time' => '15:00:00', 'is_active' => true]);

    $weekly = WeeklySchedule::create(['name' => 'W1', 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(6)->toDateString(), 'status' => 'draft']);

    $employee = Employee::factory()->create();

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $employee->id,
        'schedule_id' => $scheduleA->id,
        'assignment_date' => now()->toDateString(),
        'is_manual' => false,
    ]);

    $rows = [
        ['employee_id' => $employee->id, 'schedule_id' => $scheduleB->id, 'assignment_date' => now()->toDateString(), 'is_manual' => true],
    ];

    Permission::create(['name' => 'schedules.assign']);
    $user = User::factory()->create();
    $user->givePermissionTo('schedules.assign');

    // ensure route exists to avoid RouteNotFound during redirect
    Route::get('/__dummy', fn () => 'ok')->name('scheduling.assign_grid');

    $component = Livewire::actingAs($user)->test(AssignGrid::class, ['weekly_schedule_id' => $weekly->id])
        ->set('form.rows_json', json_encode($rows))
        ->call('preflight')
        ->assertSet('preflightOk', false);

    $this->assertNotEmpty($component->get('preflightErrors'));
});

it('applies assignments when preflight passes', function () {
    if (DB::getDriverName() === 'sqlite') {
        $this->markTestSkipped('Livewire grid tests require PostgreSQL.');
    }
    $schedule = Schedule::create(['name' => 'C', 'start_time' => '08:00:00', 'end_time' => '10:00:00', 'is_active' => true]);

    $weekly = WeeklySchedule::create(['name' => 'W2', 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(6)->toDateString(), 'status' => 'draft']);

    $employee = Employee::factory()->create();

    $rows = [
        ['employee_id' => $employee->id, 'schedule_id' => $schedule->id, 'assignment_date' => now()->toDateString(), 'is_manual' => true],
    ];

    Permission::create(['name' => 'schedules.assign']);
    $user = User::factory()->create();
    $user->givePermissionTo('schedules.assign');

    Route::get('/__dummy', fn () => 'ok')->name('scheduling.assign_grid');

    $component = Livewire::actingAs($user)->test(AssignGrid::class, ['weekly_schedule_id' => $weekly->id])
        ->set('form.rows_json', json_encode($rows))
        ->call('preflight')
        ->assertSet('preflightOk', true);

    $component->call('apply');

    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $employee->id,
    ]);
});
