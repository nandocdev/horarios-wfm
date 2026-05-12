<?php

declare(strict_types=1);

use App\Modules\SchedulingModule\Models\BreakTemplate;
use App\Modules\SchedulingModule\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('creates schedule and break template with ulid primary keys', function () {
    $schedule = Schedule::create([
        'name' => 'Gran Turno',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
        'lunch_minutes' => 60,
        'break_minutes' => 30,
        'total_minutes' => 540,
        'is_active' => true,
    ]);

    expect($schedule->id)->toBeString();
    expect(Str::length($schedule->id))->toBe(26);
    expect($schedule->fresh()->id)->toBe($schedule->id);

    $breakTemplate = BreakTemplate::create([
        'schedule_id' => $schedule->id,
        'name' => 'Almuerzo largo',
        'start_time' => '12:00:00',
        'duration_minutes' => 60,
    ]);

    expect($breakTemplate->id)->toBeString();
    expect(Str::length($breakTemplate->id))->toBe(26);
    expect($breakTemplate->schedule->id)->toBe($schedule->id);

    expect($schedule->breakTemplates()->first()->id)->toBe($breakTemplate->id);
});
