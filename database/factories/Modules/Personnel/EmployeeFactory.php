<?php

namespace Database\Factories\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'EMP-'.fake()->unique()->numerify('####'),
            'employee_role_id' => EmployeeRole::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'identity_number' => fake()->unique()->bothify('###-######-####?'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('####-####'),
            'hired_at' => fake()->dateTimeBetween('-5 years', 'now'),
            'status' => PersonnelService::STATUS_ACTIVO,
            'inss_insured' => fake()->boolean(),
        ];
    }
}
