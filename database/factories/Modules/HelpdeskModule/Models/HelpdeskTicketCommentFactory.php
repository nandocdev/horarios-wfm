<?php

declare(strict_types=1);

namespace Database\Factories\Modules\HelpdeskModule\Models;

use App\Modules\HelpdeskModule\Models\HelpdeskTicketComment;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpdeskTicketCommentFactory extends Factory
{
    protected $model = HelpdeskTicketComment::class;

    public function definition(): array
    {
        return [
            'ticket_id' => HelpdeskTicketFactory::new(),
            'author_id' => Employee::factory(),
            'content' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }
}
