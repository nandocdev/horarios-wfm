<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition()
    {
        return [
            'entity_type' => User::class,
            'entity_id' => $this->faker->numberBetween(1, 100),
            'action' => 'created',
            'before' => null,
            'after' => ['name' => $this->faker->name],
            'ip_address' => $this->faker->ipv4,
            'user_id' => User::factory(),
        ];
    }
}
