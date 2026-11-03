<?php

namespace Database\Factories\Modules\Producers;

use App\Modules\Producers\Models\Producer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producer>
 */
class ProducerFactory extends Factory
{
    protected $model = Producer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PRO-'.fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'identity_number' => fake()->optional()->bothify('###-######-####?'),
            'phone' => fake()->numerify('####-####'),
            'address' => fake()->optional()->address(),
            'community' => fake()->city(),
            'municipality' => fake()->city(),
            'department' => fake()->state(),
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
