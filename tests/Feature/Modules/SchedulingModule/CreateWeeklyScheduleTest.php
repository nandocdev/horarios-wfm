<?php

declare(strict_types=1);

use App\Modules\SchedulingModule\Actions\CreateWeeklyScheduleAction;
use App\Modules\SchedulingModule\DTOs\CreateWeeklyScheduleDTO;
use App\Modules\SchedulingModule\Models\WeeklySchedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a weekly schedule successfully', function () {
    $dto = new CreateWeeklyScheduleDTO(
        name: 'Test Week',
        startDate: new DateTime('2026-04-14'),
        endDate: new DateTime('2026-04-20')
    );

    $action = new CreateWeeklyScheduleAction;
    $weeklySchedule = $action->execute($dto);

    expect($weeklySchedule)
        ->toBeInstanceOf(WeeklySchedule::class)
        ->and($weeklySchedule->name)->toBe('Test Week')
        ->and($weeklySchedule->status)->toBe('draft')
        ->and($weeklySchedule->start_date->format('Y-m-d'))->toBe('2026-04-14')
        ->and($weeklySchedule->end_date->format('Y-m-d'))->toBe('2026-04-20');
});

it('validates unique name constraint', function () {
    WeeklySchedule::create([
        'name' => 'Duplicate Week',
        'start_date' => '2026-04-14',
        'end_date' => '2026-04-20',
        'status' => 'draft',
    ]);

    $dto = new CreateWeeklyScheduleDTO(
        name: 'Duplicate Week',
        startDate: new DateTime('2026-04-21'),
        endDate: new DateTime('2026-04-27')
    );

    $action = new CreateWeeklyScheduleAction;

    expect(fn () => $action->execute($dto))->toThrow(QueryException::class);
});
