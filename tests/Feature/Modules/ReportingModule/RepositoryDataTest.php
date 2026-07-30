<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\ReportingModule\Repositories\EloquentReportDataRepository;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use Carbon\Carbon;

beforeEach(function () {
    $this->repository = app(EloquentReportDataRepository::class);

    $this->team = Team::factory()->create(['name' => 'Soporte']);
    $this->employee = Employee::factory()->create([
        'team_id' => $this->team->id,
        'first_name' => 'Juan',
        'last_name' => 'Perez',
    ]);

    $this->filters = new ReportFilterDTO(
        dateFrom: '2026-07-01',
        dateTo: '2026-07-31',
        format: ReportFormatEnum::Pdf,
    );
});

it('getTardinessData returns tardiness from schedule exceptions', function () {
    AbsenceReasonCode::create(['name' => 'Tardanza Justificada', 'short_code' => 'T.J.', 'is_excused' => true]);

    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-15 08:15:00'),
        'end_at' => Carbon::parse('2026-07-15 08:45:00'),
        'is_full_day' => false,
    ]);

    $rows = $this->repository->getTardinessData($this->filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()->employeeName)->toBe('Juan Perez');
    expect($rows->first()->minutesLate)->toBe(30);
});

it('getLeavesData returns leave requests', function () {
    AbsenceReasonCode::create(['name' => 'Permiso Médico', 'short_code' => 'P', 'is_excused' => true]);

    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-20 09:00:00'),
        'end_at' => Carbon::parse('2026-07-20 17:00:00'),
        'is_full_day' => false,
    ]);

    $rows = $this->repository->getLeavesData($this->filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()->leaveType)->toBe('Permiso Médico');
});

it('getVacationsData returns vacation records', function () {
    AbsenceReasonCode::create(['name' => 'Vacaciones', 'short_code' => 'V.', 'is_excused' => true]);

    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-10 00:00:00'),
        'end_at' => Carbon::parse('2026-07-14 23:59:59'),
        'is_full_day' => true,
    ]);

    $rows = $this->repository->getVacationsData($this->filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()->daysTaken)->toBeGreaterThanOrEqual(5);
});

it('getVolumeDetailData returns call volume per queue', function () {
    $queue = CallQueue::create(['name' => 'Soporte', 'is_active' => true]);

    CallRecord::create([
        'cisco_call_id' => 'call-1',
        'queue_id' => $queue->id,
        'employee_id' => $this->employee->id,
        'ivr_started_at' => '2026-07-15 09:00:00',
        'contact_disposition' => 2,
        'talk_time' => 120,
        'work_time' => 30,
        'hold_time' => 0,
        'queue_time' => 10,
        'phone_number' => '3001234567',
    ]);

    $rows = $this->repository->getVolumeDetailData($this->filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()->queueName)->toBe('Soporte');
    expect($rows->first()->handled)->toBe(1);
});

it('getRawAbsenteeismData returns all absences', function () {
    AbsenceReasonCode::create(['name' => 'Ausencia Justificada', 'short_code' => 'A.J.', 'is_excused' => true]);

    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-15 09:00:00'),
        'end_at' => Carbon::parse('2026-07-15 17:00:00'),
        'is_full_day' => false,
    ]);

    $rows = $this->repository->getRawAbsenteeismData($this->filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()->causeName)->toBe('Ausencia Justificada');
});

it('filters repository data by team', function () {
    AbsenceReasonCode::create(['name' => 'Permiso', 'short_code' => 'P', 'is_excused' => true]);

    $otherTeam = Team::factory()->create(['name' => 'Otro']);
    $otherEmployee = Employee::factory()->create(['team_id' => $otherTeam->id]);

    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-15 09:00:00'),
        'end_at' => Carbon::parse('2026-07-15 17:00:00'),
    ]);
    ScheduleException::create([
        'employee_id' => $otherEmployee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-16 09:00:00'),
        'end_at' => Carbon::parse('2026-07-16 17:00:00'),
    ]);

    $teamFilter = new ReportFilterDTO(
        dateFrom: '2026-07-01',
        dateTo: '2026-07-31',
        format: ReportFormatEnum::Pdf,
        teamId: (int) $this->team->id,
    );

    $rows = $this->repository->getLeavesData($teamFilter);

    expect($rows)->toHaveCount(1);
    expect($rows->first()->employeeName)->toBe('Juan Perez');
});

it('getTardinessData returns empty when no data', function () {
    $rows = $this->repository->getTardinessData($this->filters);

    expect($rows)->toHaveCount(0);
});
