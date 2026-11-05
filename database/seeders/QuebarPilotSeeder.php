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
            foreach ($routes as $routeIndex => $sourceRoute) {
                $route = Route::query()->create([
                    'company_id' => $company->id,
                    'plant_id' => $plant->id,
                    'code' => $sourceRoute['code'],
                    'name' => $sourceRoute['name'],
                    'description' => 'Ruta oficial para piloto SUNMI. Fuente: formato de acopio 31 julio-6 agosto 2026.',
                    'active' => true,
                ]);

                $routeNumber = $routeIndex + 1;
                Rutero::query()->create([
                    'route_id' => $route->id,
                    'owner_name' => sprintf('Rutero Quebar %02d', $routeNumber),
                    'owner_identity_number' => sprintf('PILOTO-%02d', $routeNumber),
                    'owner_phone' => sprintf('8600-%04d', $routeNumber),
                    'vehicle_description' => 'Vehículo de acopio por confirmar',
                    'vehicle_plate' => sprintf('PIL-%02d', $routeNumber),
                    'driver_name' => sprintf('Rutero Quebar %02d', $routeNumber),
                    'driver_identity_number' => sprintf('RUTERO-%02d', $routeNumber),
                    'driver_phone' => sprintf('8600-%04d', $routeNumber),
                    'active' => true,
                ]);

                foreach ($sourceRoute['producers'] as $producerIndex => $sourceProducer) {
                    $producer = Producer::query()->create([
                        'code' => sprintf('%s-%03d', $sourceRoute['code'], $producerIndex + 1),
                        'full_name' => $sourceProducer['name'],
                        'identity_number' => $sourceProducer['identity'],
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
        $this->command?->info('Piloto QUEBAR cargado: 17 rutas, 617 productores y 17 cuentas SUNMI.');
    }

    /**
     * @return array<int, array{code: string, name: string, producers: array<int, array{name: string, identity: ?string}>}>
     */
    private function sourceRoutes(): array
    {
        $routes = json_decode(
            (string) file_get_contents(database_path('data/quebar_pilot_routes.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (count($routes) !== 17) {
            throw new RuntimeException('La fuente del piloto debe contener exactamente 17 rutas.');
        }

        return $routes;
    }
}
