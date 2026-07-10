<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => 'vacation',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
            'minutes' => 480,
            'status' => 'pending',
            'reason' => fake()->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'rejected']);
    }
}
