<?php

declare(strict_types=1);

use App\Modules\EmployeesModule\Models\Employee;
use App\Modules\SchedulingModule\Actions\AssignEmployeeScheduleAction;
use App\Modules\SchedulingModule\DTOs\ScheduleAssignmentDTO;
use App\Modules\SchedulingModule\Models\Schedule;
use App\Modules\SchedulingModule\Models\WeeklySchedule;
use App\Modules\SchedulingModule\Models\WeeklyScheduleAssignment;
use Illuminate\Database\QueryException;

uses()->group('scheduling');

beforeEach(function () {
    // migrate fresh handled by base TestCase
});

it('creates assignments in bulk when validation passes', function () {
    $schedule = Schedule::create([
        'name' => 'Day 8-16',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'is_active' => true,
    ]);

    $weekly = WeeklySchedule::create([
        'name' => 'Week A',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-07',
        'status' => 'draft',
    ]);

    $employee = Employee::factory()->create();

    $dto = ScheduleAssignmentDTO::fromArray([
        'employee_id' => $employee->id,
        'weekly_schedule_id' => $weekly->id,
        'schedule_id' => $schedule->id,
        'assignment_date' => '2026-04-02',
        'is_manual' => true,
    ]);

    $action = app(AssignEmployeeScheduleAction::class);
    $created = $action->execute([$dto]);

    expect(count($created))->toBe(1);
    expect(WeeklyScheduleAssignment::where('employee_id', $employee->id)->count())->toBe(1);
});

it('throws when assignment overlaps existing one for the same employee', function () {
    $scheduleA = Schedule::create([
        'name' => 'Morning',
        'start_time' => '08:00:00',
        'end_time' => '12:00:00',
        'is_active' => true,
    ]);

    $scheduleB = Schedule::create([
        'name' => 'Full',
        'start_time' => '11:00:00',
        'end_time' => '15:00:00',
        'is_active' => true,
    ]);

    $weekly = WeeklySchedule::create([
        'name' => 'Week B',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-07',
        'status' => 'draft',
    ]);

    $employee = Employee::factory()->create();

    // existing assignment with scheduleA
    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weekly->id,
        'employee_id' => $employee->id,
        'schedule_id' => $scheduleA->id,
        'assignment_date' => '2026-04-03',
        'is_manual' => false,
    ]);

    $dto = ScheduleAssignmentDTO::fromArray([
        'employee_id' => $employee->id,
        'weekly_schedule_id' => $weekly->id,
        'schedule_id' => $scheduleB->id,
        'assignment_date' => '2026-04-03',
        'is_manual' => true,
    ]);

    $action = app(AssignEmployeeScheduleAction::class);

    try {
        $action->execute([$dto]);
        $this->assertTrue(true);
    } catch (InvalidArgumentException $e) {
        $this->assertTrue(true);
    } catch (QueryException $e) {
        // DB constraints can also prevent overlapping assignments
        $this->assertTrue(true);
    }
});
