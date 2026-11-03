<?php

namespace Database\Factories\Modules\Inventory;

use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use App\Modules\Inventory\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPresentationFactory extends Factory
{
    protected $model = ProductPresentation::class;

    public function definition(): array
    {
        return [
            'product_id' => InventoryProduct::factory(),
            'name' => fake()->randomElement(['Botella 1L', 'Caja 12 Unidades', 'Caja 24 Unidades', 'Bolsa 5KG', 'Lata 500ml']),
            'description' => fake()->boolean(50) ? fake()->sentence() : null,
            'unit_id' => InventoryUnit::inRandomOrder()->first() ?? InventoryUnit::factory(),
            'conversion_factor' => fake()->randomFloat(4, 0.5, 24),
            'price' => fake()->randomFloat(2, 10, 500),
            'default' => fake()->boolean(30),
            'active' => true,
        ];
    }
}
