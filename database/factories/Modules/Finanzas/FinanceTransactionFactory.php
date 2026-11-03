<?php

namespace Database\Factories\Modules\Finanzas;

use App\Modules\Finanzas\Models\FinanceCategory;
use App\Modules\Finanzas\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceTransaction>
 */
class FinanceTransactionFactory extends Factory
{
    protected $model = FinanceTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [FinanceTransaction::TYPE_INGRESO, FinanceTransaction::TYPE_GASTO, FinanceTransaction::TYPE_PAGO];

        return [
            'code' => 'FIN-'.fake()->unique()->numerify('########'),
            'type' => fake()->randomElement($types),
            'category_id' => FinanceCategory::factory(),
            'concept' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'transaction_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'payment_method' => fake()->randomElement(['efectivo', 'transferencia', 'cheque']),
            'reference' => fake()->optional()->bothify('REF-####-####'),
            'payee' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(),
            'active' => true,
        ];
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinanceTransaction::TYPE_INGRESO,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinanceTransaction::TYPE_GASTO,
        ]);
    }

    public function payment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinanceTransaction::TYPE_PAGO,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
