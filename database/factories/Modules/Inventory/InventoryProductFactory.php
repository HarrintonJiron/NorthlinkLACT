<?php

namespace Database\Factories\Modules\Inventory;

use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryProduct>
 */
class InventoryProductFactory extends Factory
{
    protected $model = InventoryProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PRD-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'unit_id' => InventoryUnit::factory(),
            'stock' => fake()->numberBetween(0, 1000),
            'min_stock' => fake()->numberBetween(5, 50),
            'expiration_date' => fake()->optional()->dateTimeBetween('+1 month', '+2 years'),
            'active' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => fake()->numberBetween(0, 4),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
