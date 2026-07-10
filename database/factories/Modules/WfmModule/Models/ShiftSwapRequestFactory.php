<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\ShiftSwapRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftSwapRequestFactory extends Factory
{
    protected $model = ShiftSwapRequest::class;

    public function definition(): array
    {
        return [
            'requester_id' => Employee::factory(),
            'recipient_id' => Employee::factory(),
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay(),
            'status' => 'pending',
            'reason' => fake()->sentence(),
            'requester_assignment_snapshot' => [],
            'recipient_assignment_snapshot' => [],
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }
}
