<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Ruteros\Models\Rutero;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QuebarPilotSeeder extends Seeder
{
    public function run(): void
    {
        $routes = $this->sourceRoutes();
        $company = Company::query()->where('active', true)->orderBy('id')->firstOrFail();
        $plant = Plant::query()->where('company_id', $company->id)->where('active', true)->orderBy('id')->firstOrFail();
        $adminId = User::query()->where('is_admin', true)->value('id');

        if (! $adminId) {
            throw new RuntimeException('Se necesita un usuario administrador para registrar las asignaciones.');
        }

        DB::transaction(function () use ($routes, $company, $plant, $adminId): void {
            foreach ($routes as $sourceRoute) {
                $route = Route::query()->create([
                    'company_id' => $company->id,
                    'plant_id' => $plant->id,
                    'code' => $sourceRoute['code'],
                    'name' => $sourceRoute['name'],
                    'description' => 'Ruta operativa QUEBAR. Fuente más reciente recibida: planillas del 23 al 29 de octubre de 2026.',
                    'active' => true,
                ]);

                Rutero::query()->create([
                    'route_id' => $route->id,
                    'owner_name' => 'Por confirmar',
                    'owner_identity_number' => null,
                    'owner_phone' => null,
                    'vehicle_description' => 'Por confirmar',
                    'vehicle_plate' => 'PEND-'.$sourceRoute['code'],
                    'driver_name' => 'Por confirmar',
                    'driver_identity_number' => null,
                    'driver_phone' => null,
                    'active' => true,
                ]);

                foreach ($sourceRoute['producers'] as $producerIndex => $sourceProducer) {
                    $producer = Producer::query()->create([
                        'code' => sprintf('%s-%03d', $sourceRoute['code'], $producerIndex + 1),
                        'full_name' => $sourceProducer['name'],
                        'identity_number' => $sourceProducer['identity'],
                        'phone' => $sourceProducer['phone'],
                        'payment_method' => $sourceProducer['payment_method'],
                        'bank_name' => $sourceProducer['bank_name'],
                        'bank_account_number' => $sourceProducer['bank_account_number'],
                        'bank_account_holder' => $sourceProducer['bank_account_holder'],
                        'payment_notes' => $sourceProducer['payment_method'] === 'transfer'
                            ? 'Cuenta bancaria pendiente de completar; el archivo fuente solo indica depósito bancario.'
                            : null,
                        'community' => $sourceRoute['name'],
                        'municipality' => 'Waslala',
                        'department' => 'RACCN',
                        'active' => true,
                    ]);

                    ProducerRouteAssignment::query()->create([
                        'producer_id' => $producer->id,
                        'route_id' => $route->id,
                        'assigned_at' => today()->toDateString(),
                        'assigned_by' => $adminId,
                    ]);
                }
            }
        });

        $this->call(SunmiRuteroDemoSeeder::class);
        $producerCount = collect($routes)->sum(fn (array $route): int => count($route['producers']));
        $this->command?->info("Datos QUEBAR cargados: {$routes->count()} rutas, {$producerCount} productores y 17 cuentas SUNMI.");
    }

    /**
     * @return Collection<int, array{code: string, name: string, producers: array<int, array{name: string, identity: ?string, phone: ?string, payment_method: string, bank_name: ?string, bank_account_number: ?string, bank_account_holder: ?string}>}>
     */
    private function sourceRoutes(): Collection
    {
        $routes = json_decode(
            (string) file_get_contents(database_path('data/quebar_pilot_routes.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (count($routes) < 17) {
            throw new RuntimeException('La fuente operativa debe contener al menos 17 rutas.');
        }

        return collect($routes);
    }
}
