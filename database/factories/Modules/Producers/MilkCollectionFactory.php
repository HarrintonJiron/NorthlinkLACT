<?php

namespace Database\Factories\Modules\Producers;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilkCollection>
 */
class MilkCollectionFactory extends Factory
{
    protected $model = MilkCollection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plant_id' => Plant::factory(),
            'route_id' => Route::factory(),
            'producer_id' => Producer::factory(),
            'collection_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'liters' => fake()->randomFloat(1, 10, 80),
            'temperature' => fake()->randomFloat(1, 15, 35),
            'acidity' => fake()->optional()->randomFloat(2, 12, 18),
            'collected_by' => User::factory(),
        ];
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_date' => now()->toDateString(),
        ]);
    }

    public function yesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_date' => now()->subDay()->toDateString(),
        ]);
    }
}
