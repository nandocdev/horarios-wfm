<?php

declare(strict_types=1);

namespace Database\Factories\Modules\HelpdeskModule\Models;

use App\Modules\HelpdeskModule\Models\HelpdeskCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpdeskCategoryFactory extends Factory
{
    protected $model = HelpdeskCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->word(),
            'description' => fake()->sentence(),
            'sla_hours' => 48,
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}
