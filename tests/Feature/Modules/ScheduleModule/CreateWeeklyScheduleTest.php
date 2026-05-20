<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\WfmModule\Models\WeeklySchedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a weekly schedule with correct dates', function () {
    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    expect($weeklySchedule->week_start_date->format('Y-m-d'))->toBe('2026-04-13')
        ->and($weeklySchedule->week_end_date->format('Y-m-d'))->toBe('2026-04-19');
});

it('validates unique week start date constraint', function () {
    WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    $this->expectException(QueryException::class);

    WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);
});
