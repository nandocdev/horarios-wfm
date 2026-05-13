<?php

declare(strict_types=1);

namespace Database\Factories\Modules\PersonnelModule\Models;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => $this->faker->unique()->numerify('EMP###'),
            'username' => $this->faker->unique()->userName(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'phone' => $this->faker->phoneNumber(),
            'mobile_phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'hire_date' => $this->faker->date('Y-m-d', '-5 years'),
            'salary' => $this->faker->randomFloat(2, 1000, 10000),
            'is_active' => true,
            'is_manager' => false,
            'metadata' => [],
        ];
    }
}
