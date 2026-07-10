<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\WeeklySchedule;
use App\Modules\WfmModule\Models\WeeklyScheduleAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyScheduleAssignmentFactory extends Factory
{
    protected $model = WeeklyScheduleAssignment::class;

    public function definition(): array
    {
        return [
            'weekly_schedule_id' => WeeklySchedule::factory(),
            'employee_id' => Employee::factory(),
            'schedule_id' => Schedule::factory(),
            'day_of_week' => Carbon::today()->dayOfWeekIso,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'lunch_start_time' => '12:00:00',
            'lunch_end_time' => '13:00:00',
            'break_start_time' => '10:00:00',
            'break_end_time' => '10:15:00',
            'is_replaced' => false,
        ];
    }
}
