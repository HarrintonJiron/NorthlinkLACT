<?php

namespace Database\Factories\Modules\Ruteros;

use App\Modules\Producers\Models\Route;
use App\Modules\Ruteros\Models\Rutero;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rutero>
 */
class RuteroFactory extends Factory
{
    protected $model = Rutero::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'route_id' => Route::factory(),
            'owner_name' => fake()->name(),
            'owner_identity_number' => fake()->unique()->bothify('###-######-####?'),
            'owner_phone' => fake()->numerify('####-####'),
            'vehicle_description' => fake()->word().' '.fake()->word(),
            'vehicle_plate' => fake()->bothify('?-####'),
            'driver_name' => fake()->name(),
            'driver_identity_number' => fake()->optional()->bothify('###-######-####?'),
            'driver_phone' => fake()->optional()->numerify('####-####'),
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
