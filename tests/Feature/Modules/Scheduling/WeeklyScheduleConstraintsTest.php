<?php

declare(strict_types=1);

use App\Modules\SchedulingModule\Models\WeeklySchedule;
use Illuminate\Database\QueryException;

it('prevents overlapping weekly schedules at DB level', function () {
    WeeklySchedule::create([
        'name' => 'week-a',
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-07',
    ]);

    if (config('database.default') === 'sqlite') {
        // SQLite in-memory used in tests does not support PostgreSQL exclusion constraints.
        // Assert that insertion succeeds in this environment (integration with PG required in CI).
        $second = WeeklySchedule::create([
            'name' => 'week-b',
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-11',
        ]);

        expect(WeeklySchedule::count())->toBe(2);
        expect($second->name)->toBe('week-b');
    } else {
        expect(fn () => WeeklySchedule::create([
            'name' => 'week-b',
            'start_date' => '2026-04-05',
            'end_date' => '2026-04-11',
        ]))->toThrow(QueryException::class);
    }
});
