<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\ScheduleModule;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduleException;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use App\Modules\WfmModule\Services\ScheduleValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ScheduleValidationService;
});

test('it validates shift times correctly', function () {
    expect($this->service->validateTimes('08:00', '16:00'))->toBeTrue();
    expect($this->service->validateTimes('16:00', '08:00'))->toBeFalse();
    expect($this->service->validateTimes('08:00', '08:00'))->toBeFalse();
});

test('it detects weekly assignment overlaps correctly', function () {
    $employee = Employee::factory()->create();
    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'week_end_date' => now()->endOfWeek()->toDateString(),
    ]);
    $schedule = Schedule::create([
        'name' => 'Test Shift',
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'total_minutes' => 480,
    ]);

    WeeklyScheduleAssignment::create([
        'weekly_schedule_id' => $weeklySchedule->id,
        'employee_id' => $employee->id,
        'day_of_week' => 1,
        'schedule_id' => $schedule->id,
        'start_time' => '08:00:00',
        'end_time' => '16:00:00',
        'is_replaced' => false,
    ]);

    // Colisión total
    expect($this->service->hasWeeklyAssignmentOverlap($employee->id, $weeklySchedule->id, 1, '09:00', '15:00'))->toBeTrue();

    // Colisión parcial inicio
    expect($this->service->hasWeeklyAssignmentOverlap($employee->id, $weeklySchedule->id, 1, '07:00', '09:00'))->toBeTrue();

    // Colisión parcial fin
    expect($this->service->hasWeeklyAssignmentOverlap($employee->id, $weeklySchedule->id, 1, '15:00', '17:00'))->toBeTrue();

    // Caso borde: fin == existing.start (permitido)
    expect($this->service->hasWeeklyAssignmentOverlap($employee->id, $weeklySchedule->id, 1, '07:00', '08:00'))->toBeFalse();

    // Caso borde: inicio == existing.end (permitido)
    expect($this->service->hasWeeklyAssignmentOverlap($employee->id, $weeklySchedule->id, 1, '16:00', '17:00'))->toBeFalse();
});

test('it detects exception overlaps correctly', function () {
    $employee = Employee::factory()->create();
    $date = now()->addDays(2)->toDateString();

    $reason = AbsenceReasonCode::create([
        'name' => 'Cita Médica',
        'short_code' => 'MED',
        'requires_attachment' => false,
        'is_excused' => true,
    ]);

    ScheduleException::create([
        'employee_id' => $employee->id,
        'absence_reason_code_id' => $reason->id,
        'start_at' => Carbon::parse($date.' 10:00:00'),
        'end_at' => Carbon::parse($date.' 12:00:00'),
        'is_full_day' => false,
    ]);

    // Colisión total
    expect($this->service->hasExceptionOverlap($employee->id, $date, '10:30', '11:30'))->toBeTrue();

    // Caso borde: fin == existing.start (permitido)
    expect($this->service->hasExceptionOverlap($employee->id, $date, '09:00', '10:00'))->toBeFalse();

    // Caso borde: inicio == existing.end (permitido)
    expect($this->service->hasExceptionOverlap($employee->id, $date, '12:00', '13:00'))->toBeFalse();
});
