<?php

namespace Database\Factories\Modules\Finanzas;

use App\Modules\Finanzas\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceCategory>
 */
class FinanceCategoryFactory extends Factory
{
    protected $model = FinanceCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(['ingreso', 'gasto', 'pago']),
            'active' => true,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ingreso',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'gasto',
        ]);
    }

    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'pago',
        ]);
    }
}
