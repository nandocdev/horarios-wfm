<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Actions\UpdateEmployeeDayAssignmentAction;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

it('updates an individual assignment for an employee', function () {
    $schedule = Schedule::create([
        'name' => 'Turno A',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
    ]);

    $weekly = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'published',
    ]);

    $employee = Employee::factory()->create();

    $assignment = WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'schedule_id' => $schedule->id,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
    ]);

    $action = app(UpdateEmployeeDayAssignmentAction::class);
    
    $newData = [
        'schedule_id' => $schedule->id,
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
        'lunch_start_time' => '13:00:00',
        'lunch_minutes' => 60,
        'break_start_time' => null,
    ];

    $action->execute($assignment->id, $newData);

    $today = now()->toDateString();
    $this->assertDatabaseHas('weekly_schedule_assignments', [
        'id' => $assignment->id,
        'start_time' => $today . ' 09:00:00',
        'lunch_start_time' => $today . ' 13:00:00',
        'lunch_end_time' => $today . ' 14:00:00',
    ]);
});
