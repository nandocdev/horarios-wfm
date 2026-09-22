<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Location\Infrastructure\Persistence\Models\District;
use Src\Location\Infrastructure\Persistence\Models\Township;

/**
 * @extends Factory<Township>
 */
class TownshipFactory extends Factory
{
    protected $model = Township::class;

    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'name' => fake()->streetName(),
        ];
    }
}
