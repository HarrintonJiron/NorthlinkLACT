<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Services\MilkCollectionService;
use App\Modules\Ruteros\Models\Rutero;
use Illuminate\Support\Str;

trait CreatesAcopioFixtures
{
    protected User $user;

    protected Company $company;

    protected Plant $plant;

    protected function setUpAcopio(): void
    {
        $this->user = User::factory()->create();

        $this->company = Company::query()->create([
            'name' => 'Northlink LACT',
            'legal_name' => 'Northlink LACT',
            'tax_id' => 'J0310000000000',
            'active' => true,
        ]);

        $this->plant = Plant::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Planta Matagalpa',
            'code' => 'PLT-TEST',
            'active' => true,
        ]);
    }

    protected function createRoute(array $overrides = []): Route
    {
        $suffix = strtoupper(Str::random(4));

        return Route::query()->create(array_merge([
            'company_id' => $this->company->id,
            'plant_id' => $this->plant->id,
            'code' => 'RUT-'.$suffix,
            'name' => 'Ruta '.$suffix,
            'active' => true,
        ], $overrides));
    }

    protected function createRutero(?Route $route = null, array $overrides = []): Rutero
    {
        $suffix = $route?->code ?? strtoupper(Str::random(4));

        return Rutero::query()->create(array_merge([
            'owner_name' => 'Dueño '.$suffix,
            'owner_identity_number' => sprintf('001-%05d-0001A', random_int(10000, 99999)),
            'owner_phone' => '8888-0000',
            'vehicle_description' => 'Camión cisterna',
            'vehicle_plate' => 'M-'.($route?->id ?? random_int(100, 999)),
            'driver_name' => 'Encargado '.$suffix,
            'driver_identity_number' => sprintf('441-%05d-0002B', random_int(10000, 99999)),
            'driver_phone' => '8777-0000',
            'active' => true,
        ], $route ? ['route_id' => $route->id] : [], $overrides));
    }

    protected function createProducer(?Route $route = null, array $overrides = []): Producer
    {
        $suffix = strtoupper(Str::random(4));

        $producer = Producer::query()->create(array_merge([
            'code' => 'PRO-'.$suffix,
            'full_name' => 'Productor '.$suffix,
            'identity_number' => sprintf('441-%05d-0001A', random_int(10000, 99999)),
            'phone' => '8777-0000',
            'active' => true,
        ], $overrides));

        if ($route) {
            $this->assignProducer($producer, $route);
        }

        return $producer->fresh(['activeAssignment.route']);
    }

    protected function assignProducer(Producer $producer, Route $route): ProducerRouteAssignment
    {
        return ProducerRouteAssignment::query()->create([
            'producer_id' => $producer->id,
            'route_id' => $route->id,
            'assigned_at' => now()->toDateString(),
            'assigned_by' => $this->user->id,
        ]);
    }

    protected function createPrice(float $price = 18.5): MilkPrice
    {
        return MilkPrice::query()->create([
            'company_id' => $this->company->id,
            'plant_id' => $this->plant->id,
            'price_per_liter' => $price,
            'effective_from' => now()->subYear()->toDateString(),
            'active' => true,
        ]);
    }

    protected function collectMilk(Producer $producer, float $liters, ?string $date = null, ?Route $route = null, ?float $temperature = 25): MilkCollection
    {
        $route ??= $producer->activeAssignment?->route;

        return app(MilkCollectionService::class)->record(
            $route,
            $producer->id,
            $date ?? now()->toDateString(),
            $liters,
            $this->user,
            temperature: $temperature,
        );
    }
}
