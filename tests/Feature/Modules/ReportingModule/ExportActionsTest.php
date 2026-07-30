<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\ReportingModule\Actions\ExportLeavesAction;
use App\Modules\ReportingModule\Actions\ExportTardinessAction;
use App\Modules\ReportingModule\Actions\ExportVolumeDetailAction;
use App\Modules\ReportingModule\DTOs\ReportFilterDTO;
use App\Modules\ReportingModule\Enums\ReportFormatEnum;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    $this->employee = Employee::factory()->create();
    $this->teamId = (int) $this->employee->team_id;

    $this->filters = new ReportFilterDTO(
        dateFrom: '2026-07-01',
        dateTo: '2026-07-31',
        format: ReportFormatEnum::Pdf,
        teamId: $this->teamId,
    );

    $this->xlsFilters = new ReportFilterDTO(
        dateFrom: '2026-07-01',
        dateTo: '2026-07-31',
        format: ReportFormatEnum::Xls,
        teamId: $this->teamId,
    );
});

it('ExportTardinessAction returns PDF StreamedResponse', function () {
    AbsenceReasonCode::create(['name' => 'Tardanza', 'short_code' => 'T.I.', 'is_excused' => false]);
    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-15 08:10:00'),
        'end_at' => Carbon::parse('2026-07-15 08:40:00'),
    ]);

    $response = app(ExportTardinessAction::class)->execute($this->filters);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('ExportLeavesAction returns PDF StreamedResponse', function () {
    AbsenceReasonCode::create(['name' => 'Permiso', 'short_code' => 'P', 'is_excused' => true]);
    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-20 09:00:00'),
        'end_at' => Carbon::parse('2026-07-20 17:00:00'),
    ]);

    $response = app(ExportLeavesAction::class)->execute($this->filters);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('ExportTardinessAction returns XLS StreamedResponse', function () {
    AbsenceReasonCode::create(['name' => 'Tardanza', 'short_code' => 'T.I.', 'is_excused' => false]);
    ScheduleException::create([
        'employee_id' => $this->employee->id,
        'absence_reason_code_id' => AbsenceReasonCode::first()->id,
        'start_at' => Carbon::parse('2026-07-15 08:10:00'),
        'end_at' => Carbon::parse('2026-07-15 08:40:00'),
    ]);

    $response = app(ExportTardinessAction::class)->execute($this->xlsFilters);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});

it('ExportVolumeDetailAction returns PDF with valid data', function () {
    $queue = CallQueue::create(['name' => 'TestQueue', 'is_active' => true]);

    CallRecord::create([
        'cisco_call_id' => 'c1',
        'queue_id' => $queue->id,
        'employee_id' => $this->employee->id,
        'ivr_started_at' => '2026-07-15 10:00:00',
        'contact_disposition' => 2,
        'talk_time' => 180,
        'work_time' => 45,
        'hold_time' => 0,
        'queue_time' => 15,
        'phone_number' => '3001234567',
    ]);

    $response = app(ExportVolumeDetailAction::class)->execute($this->filters);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});
