<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Models\LeaveRequest;
use App\Modules\WfmModule\Models\LeaveRequestApproval;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestApprovalFactory extends Factory
{
    protected $model = LeaveRequestApproval::class;

    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'approver_id' => Employee::factory(),
            'status' => 'pending',
            'comment' => fake()->sentence(),
            'step_order' => 1,
        ];
    }
}
