<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Location\Infrastructure\Persistence\Models\District;
use Src\Location\Infrastructure\Persistence\Models\Province;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'name' => fake()->city(),
        ];
    }
}
