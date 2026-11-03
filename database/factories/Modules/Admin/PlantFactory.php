<?php

namespace Database\Factories\Modules\Admin;

use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plant>
 */
class PlantFactory extends Factory
{
    protected $model = Plant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->city().' Plant',
            'code' => 'PLT-'.fake()->unique()->numerify('####'),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
