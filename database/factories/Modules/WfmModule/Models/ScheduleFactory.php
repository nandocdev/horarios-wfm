<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\WfmModule\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'total_minutes' => 540,
            'break_minutes' => 15,
            'lunch_minutes' => 60,
            'is_lunch_paid' => false,
            'is_break_paid' => true,
            'is_active' => true,
            'allowed_days' => [1, 2, 3, 4, 5],
        ];
    }
}
