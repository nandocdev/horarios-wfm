<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Location\Infrastructure\Persistence\Models\Province;

/**
 * @extends Factory<Province>
 */
class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->state(),
            'code' => strtoupper(fake()->lexify('??')),
        ];
    }
}
