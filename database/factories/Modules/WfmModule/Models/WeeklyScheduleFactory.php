<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\WfmModule\Models\WeeklySchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyScheduleFactory extends Factory
{
    protected $model = WeeklySchedule::class;

    public function definition(): array
    {
        $weekStart = Carbon::today()->startOfWeek();

        return [
            'week_start_date' => $weekStart->toDateString(),
            'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
            'status' => 'draft',
        ];
    }
}
