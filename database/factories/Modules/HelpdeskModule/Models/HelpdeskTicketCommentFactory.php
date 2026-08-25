<?php

declare(strict_types=1);

namespace Database\Factories\Modules\HelpdeskModule\Models;

use App\Modules\CoreModule\Models\User;
use App\Modules\HelpdeskModule\Models\HelpdeskTicketComment;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpdeskTicketCommentFactory extends Factory
{
    protected $model = HelpdeskTicketComment::class;

    public function definition(): array
    {
        return [
            'ticket_id' => HelpdeskTicketFactory::new(),
            // author_id referencia users.id (esquema de actores).
            'author_id' => User::factory(),
            'content' => fake()->paragraph(),
            'is_internal' => false,
        ];
    }
}
