<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ScheduleException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleExceptionFactory extends Factory
{
    protected $model = ScheduleException::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'absence_reason_code_id' => AbsenceReasonCode::factory(),
            'start_at' => Carbon::today()->setHour(10),
            'end_at' => Carbon::today()->setHour(12),
            'is_full_day' => false,
            'remarks' => fake()->sentence(),
        ];
    }
}
