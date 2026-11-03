<?php

namespace Database\Factories\Modules\Inventory;

use App\Modules\Inventory\Models\InventoryUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryUnit>
 */
class InventoryUnitFactory extends Factory
{
    protected $model = InventoryUnit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = ['KG', 'G', 'L', 'ML', 'UND', 'CJ', 'GAL', 'QQ', 'LB'];

        return [
            'code' => fake()->unique()->randomElement($units),
            'name' => fake()->word(),
            'active' => true,
        ];
    }
}
