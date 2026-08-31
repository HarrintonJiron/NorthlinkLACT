<?php

namespace Database\Factories\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
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
            'employee_role_id' => EmployeeRole::factory(),
            'full_name' => fake()->name(),
            'identity_number' => fake()->unique()->bothify('###-######-####?'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('####-####'),
            'hired_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'active' => true,
        ];
    }
}
