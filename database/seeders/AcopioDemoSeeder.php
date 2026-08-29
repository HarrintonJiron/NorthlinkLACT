<?php

namespace Database\Seeders;

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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AcopioDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create([
            'name' => 'Operador de acopio',
            'email' => 'acopio@northlink.test',
            'password' => Hash::make('password'),
        ]);

        $company = Company::query()->first() ?? Company::query()->create([
            'name' => 'Northlink LACT',
            'legal_name' => 'Northlink LACT S.A.',
            'tax_id' => 'J0310000000001',
            'active' => true,
        ]);

        $plant = Plant::query()->first() ?? Plant::query()->create([
            'company_id' => $company->id,
            'name' => 'Planta Matagalpa',
            'code' => 'PLT-001',
            'active' => true,
        ]);

        MilkPrice::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'plant_id' => $plant->id,
                'effective_from' => '2026-01-01',
            ],
            [
                'price_per_liter' => 18.50,
                'effective_until' => null,
                'active' => true,
            ]
        );

        $norte = $this->route($company, $plant, 'RUT-0001', [
            'name' => 'Ruta Matagalpa Norte',
        ]);

        $sur = $this->route($company, $plant, 'RUT-0002', [
            'name' => 'Ruta Jinotega Sur',
        ]);

        $this->rutero($norte, [
            'full_name' => 'Mario Palacios',
            'identity_number' => '441-01010-0001A',
            'phone' => '8888-1010',
            'vehicle_plate' => 'M-1010',
        ]);

        $this->rutero($sur, [
            'full_name' => 'Yadira Cano',
            'identity_number' => '441-02020-0002B',
            'phone' => '8777-2020',
            'vehicle_plate' => 'JI-2020',
        ]);

        $maria = $this->producer($norte, $user, [
            'code' => 'PRO-0001',
            'full_name' => 'María Elena Castillo',
            'identity_number' => '441-11223-0004C',
            'phone' => '8855-1100',
            'community' => 'Yasica Sur',
            'municipality' => 'Matagalpa',
            'department' => 'Matagalpa',
        ]);

        $jose = $this->producer($norte, $user, [
            'code' => 'PRO-0002',
            'full_name' => 'José Antonio López',
            'identity_number' => '441-22334-0005D',
            'phone' => '8844-2200',
            'community' => 'San Ramón',
            'municipality' => 'San Ramón',
            'department' => 'Matagalpa',
        ]);

        $ana = $this->producer($norte, $user, [
            'code' => 'PRO-0003',
            'full_name' => 'Ana Lucía Herrera',
            'identity_number' => '441-33445-0006E',
            'phone' => '8833-3300',
            'community' => 'El Tuma',
            'municipality' => 'El Tuma-La Dalia',
            'department' => 'Matagalpa',
        ]);

        $carlos = $this->producer($sur, $user, [
            'code' => 'PRO-0004',
            'full_name' => 'Carlos Méndez',
            'identity_number' => '441-44556-0007F',
            'phone' => '8822-4400',
            'community' => 'San Rafael',
            'municipality' => 'Jinotega',
            'department' => 'Jinotega',
        ]);

        $rosa = $this->producer($sur, $user, [
            'code' => 'PRO-0005',
            'full_name' => 'Rosa Isabel Gutiérrez',
            'identity_number' => '441-55667-0008G',
            'phone' => '8811-5500',
            'community' => 'La Concordia',
            'municipality' => 'La Concordia',
            'department' => 'Jinotega',
        ]);

        $this->producer($norte, $user, [
            'code' => 'PRO-0006',
            'full_name' => 'Pedro Altamirano',
            'identity_number' => '441-66778-0009H',
            'phone' => '8800-6600',
            'community' => 'Sébaco',
            'municipality' => 'Sébaco',
            'department' => 'Matagalpa',
            'active' => false,
        ]);

        $service = app(MilkCollectionService::class);
        $friday = now()->startOfDay()->isFriday()
            ? now()->startOfDay()->copy()
            : now()->startOfDay()->copy()->previous(Carbon::FRIDAY);
        $this->collectWeek($service, $maria, $norte, $user, $friday, [
            0 => 42.5,
            1 => 40.0,
            2 => 38.5,
            3 => 41.0,
        ]);

        $this->collectWeek($service, $jose, $norte, $user, $friday, [
            0 => 28.0,
            2 => 26.5,
            3 => 27.0,
        ]);

        $this->collectWeek($service, $ana, $norte, $user, $friday, [
            1 => 19.5,
        ]);

        $this->collectWeek($service, $carlos, $sur, $user, $friday, [
            0 => 33.0,
            1 => 31.5,
            2 => 32.0,
        ]);

        $this->collectWeek($service, $rosa, $sur, $user, $friday, [
            0 => 22.0,
            1 => 23.5,
            2 => 21.0,
            3 => 24.0,
            4 => 22.5,
        ]);

        $previousFriday = $friday->copy()->subWeek();
        $this->collectWeek($service, $maria, $norte, $user, $previousFriday, [
            0 => 39.0,
            1 => 37.5,
            2 => 40.0,
            3 => 38.0,
            4 => 36.5,
            5 => 41.0,
            6 => 39.5,
        ]);
    }

    protected function route(Company $company, Plant $plant, string $code, array $attributes): Route
    {
        return Route::withTrashed()->updateOrCreate(
            ['code' => $code],
            array_merge([
                'company_id' => $company->id,
                'plant_id' => $plant->id,
                'active' => true,
                'deleted_at' => null,
            ], $attributes)
        );
    }

    protected function rutero(Route $route, array $attributes): Rutero
    {
        return Rutero::withTrashed()->updateOrCreate(
            ['identity_number' => $attributes['identity_number']],
            array_merge([
                'route_id' => $route->id,
                'active' => true,
                'deleted_at' => null,
            ], $attributes)
        );
    }

    protected function producer(Route $route, User $user, array $attributes): Producer
    {
        $producer = Producer::withTrashed()->updateOrCreate(
            ['code' => $attributes['code']],
            array_merge([
                'active' => true,
                'deleted_at' => null,
            ], $attributes)
        );

        $assignment = ProducerRouteAssignment::query()
            ->where('producer_id', $producer->id)
            ->whereNull('ended_at')
            ->first();

        if (! $assignment) {
            ProducerRouteAssignment::query()->create([
                'producer_id' => $producer->id,
                'route_id' => $route->id,
                'assigned_at' => now()->toDateString(),
                'assigned_by' => $user->id,
            ]);
        } elseif ((int) $assignment->route_id !== (int) $route->id) {
            $assignment->update(['ended_at' => now()->toDateString()]);
            ProducerRouteAssignment::query()->create([
                'producer_id' => $producer->id,
                'route_id' => $route->id,
                'assigned_at' => now()->toDateString(),
                'assigned_by' => $user->id,
            ]);
        }

        return $producer->fresh('activeAssignment.route');
    }

    protected function collectWeek(
        MilkCollectionService $service,
        Producer $producer,
        Route $route,
        User $user,
        Carbon $friday,
        array $litersByOffset
    ): void {
        foreach ($litersByOffset as $offset => $liters) {
            $date = $friday->copy()->addDays($offset)->toDateString();
            $service->record($route, $producer->id, $date, (float) $liters, $user);
        }
    }
}
