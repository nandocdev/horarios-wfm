<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\ScheduleModule;

use App\Modules\WfmModule\Models\WeeklySchedule;
use Illuminate\Database\QueryException;

it('creates a weekly schedule with correct dates', function () {
    $weeklySchedule = WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    expect($weeklySchedule->week_start_date->format('Y-m-d'))->toBe('2026-04-13')
        ->and($weeklySchedule->week_end_date->format('Y-m-d'))->toBe('2026-04-19');
});

it('validates unique published week start date constraint', function () {
    // Invariante actual (índice parcial weekly_schedules_published_unique):
    // múltiples drafts por semana son válidos; solo un 'published' por semana.
    WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'draft',
    ]);

    WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'published',
    ]);

    $this->expectException(QueryException::class);

    // Segunda publicación para la misma semana: viola weekly_schedules_published_unique.
    WeeklySchedule::create([
        'week_start_date' => '2026-04-13',
        'week_end_date' => '2026-04-19',
        'status' => 'published',
    ]);
});
