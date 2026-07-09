<?php

declare(strict_types=1);

namespace Database\Factories\Modules\HelpdeskModule\Models;

use App\Modules\HelpdeskModule\Enums\TicketPriority;
use App\Modules\HelpdeskModule\Enums\TicketStatus;
use App\Modules\HelpdeskModule\Models\HelpdeskTicket;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpdeskTicketFactory extends Factory
{
    protected $model = HelpdeskTicket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category_id' => HelpdeskCategoryFactory::new(),
            'creator_id' => Employee::factory(),
            'assigned_agent_id' => null,
            'status' => TicketStatus::New->value,
            'priority' => TicketPriority::Medium->value,
        ];
    }
}
