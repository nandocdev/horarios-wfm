<?php

declare(strict_types=1);

namespace Database\Factories\Modules\WfmModule\Models;

use App\Modules\WfmModule\Models\AbsenceReasonCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceReasonCodeFactory extends Factory
{
    protected $model = AbsenceReasonCode::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'short_code' => strtoupper(fake()->unique()->lexify('???')),
            'color' => fake()->hexColor(),
        ];
    }
}
