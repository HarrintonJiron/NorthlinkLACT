<?php

namespace Database\Factories\Modules\Personnel;

use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeRole>
 */
class EmployeeRoleFactory extends Factory
{
    protected $model = EmployeeRole::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'description' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }
}
