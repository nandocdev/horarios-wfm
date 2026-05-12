<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\WfmModule\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        DB::table('schedules')->truncate();
        $shifts = [
            ['name' => '06001400', 'start' => '06:00', 'end' => '14:00'],
            ['name' => '07001500', 'start' => '07:00', 'end' => '15:00'],
            ['name' => '07301530', 'start' => '07:30', 'end' => '15:30'],
            ['name' => '08001600', 'start' => '08:00', 'end' => '16:00'],
            ['name' => '09001700', 'start' => '09:00', 'end' => '17:00'],
        ];

        foreach ($shifts as $shift) {
            $start = Carbon::parse($shift['start']);
            $end = Carbon::parse($shift['end']);

            // Si el fin es menor que el inicio, asumimos que es al día siguiente (overnight)
            if ($end->lessThan($start)) {
                $end->addDay();
            }

            $minutes = (int) $start->diffInMinutes($end);

            Schedule::updateOrCreate(
                ['start_time' => $shift['start'], 'end_time' => $shift['end']],
                [
                    'name' => $shift['name'],
                    'total_minutes' => $minutes,
                    'is_active' => true,
                    'allowed_days' => [1, 2, 3, 4, 5]
                ]
            );
        }

        $this->command->info('Turnos base (8h) creados exitosamente.');
    }
}
