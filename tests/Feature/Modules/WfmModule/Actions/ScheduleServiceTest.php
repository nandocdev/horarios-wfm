<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\WfmModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->schedule = Schedule::factory()->create([
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'lunch_minutes' => 60,
        'break_minutes' => 15,
        'total_minutes' => 540,
    ]);
    $this->service = app(ScheduleService::class);
    $this->today = Carbon::today();
    $this->dayOfWeek = $this->today->dayOfWeekIso;
});

it('returns is_off when no schedule exists for employee', function () {
    $dto = $this->service->getScheduleForEmployee(
        employeeId: $this->employee->id,
        date: $this->today,
    );

    expect($dto->is_off)->toBeTrue()
        ->and($dto->start_time)->toBeNull();
});

it('returns schedule when assignment exists', function () {
    $weekStart = $this->today->copy()->startOfWeek();
    DB::table('weekly_schedules')->insert([
        'week_start_date' => $weekStart->toDateString(),
        'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $wsId = DB::getPdo()->lastInsertId();

    DB::table('weekly_schedule_assignments')->insert([
        'weekly_schedule_id' => $wsId,
        'employee_id' => $this->employee->id,
        'schedule_id' => $this->schedule->id,
        'day_of_week' => $this->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'lunch_start_time' => '12:00:00',
        'lunch_end_time' => '13:00:00',
        'break_start_time' => '10:00:00',
        'break_end_time' => '10:15:00',
        'is_replaced' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dto = $this->service->getScheduleForEmployee(
        employeeId: $this->employee->id,
        date: $this->today,
    );

    expect($dto->is_off)->toBeFalse()
        ->and($dto->start_time)->toBe('08:00:00')
        ->and($dto->end_time)->toBe('17:00:00')
        ->and($dto->lunch_minutes)->toBe(60)
        ->and($dto->break_minutes)->toBe(15);
});

it('includes exceptions in schedule DTO', function () {
    $weekStart = $this->today->copy()->startOfWeek();
    DB::table('weekly_schedules')->insert([
        'week_start_date' => $weekStart->toDateString(),
        'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $wsId = DB::getPdo()->lastInsertId();

    DB::table('weekly_schedule_assignments')->insert([
        'weekly_schedule_id' => $wsId,
        'employee_id' => $this->employee->id,
        'schedule_id' => $this->schedule->id,
        'day_of_week' => $this->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_replaced' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $reason = AbsenceReasonCode::factory()->create();
    DB::table('schedule_exceptions')->insert([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => $reason->id,
        'start_at' => $this->today->copy()->setHour(10),
        'end_at' => $this->today->copy()->setHour(12),
        'is_full_day' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $dto = $this->service->getScheduleForEmployee(
        employeeId: $this->employee->id,
        date: $this->today,
    );

    expect($dto->exceptions)->toHaveCount(1)
        ->and($dto->exceptions[0]['is_full_day'])->toBeFalse();
});

it('returns batch schedules for multiple employees', function () {
    $employee2 = Employee::factory()->create();
    $weekStart = $this->today->copy()->startOfWeek();
    DB::table('weekly_schedules')->insert([
        'week_start_date' => $weekStart->toDateString(),
        'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $wsId = DB::getPdo()->lastInsertId();

    DB::table('weekly_schedule_assignments')->insert([
        'weekly_schedule_id' => $wsId,
        'employee_id' => $this->employee->id,
        'schedule_id' => $this->schedule->id,
        'day_of_week' => $this->dayOfWeek,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'is_replaced' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $results = $this->service->getBatchSchedules(
        employeeIds: [$this->employee->id, $employee2->id],
        date: $this->today,
    );

    expect($results)->toHaveKey($this->employee->id)
        ->and($results[$this->employee->id]->is_off)->toBeFalse()
        ->and($results)->toHaveKey($employee2->id)
        ->and($results[$employee2->id]->is_off)->toBeTrue();
});
