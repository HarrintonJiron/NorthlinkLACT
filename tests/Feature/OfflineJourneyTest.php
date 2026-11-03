<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\RouteOperatorAssignment;
use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_device_can_download_accessible_routes_for_tomorrow(): void
    {
        $user = User::factory()->create(['active' => true]);
        $company = Company::query()->create([
            'name' => 'Northlink LACT',
            'legal_name' => 'Northlink LACT',
            'tax_id' => 'J0310000000099',
            'active' => true,
        ]);
        $plant = Plant::query()->create([
            'company_id' => $company->id,
            'name' => 'Planta Quebar',
            'code' => 'QUEBAR',
            'active' => true,
        ]);
        $company->users()->attach($user->id, ['plant_id' => $plant->id]);

        $route = Route::query()->create([
            'company_id' => $company->id,
            'plant_id' => $plant->id,
            'code' => 'R-001',
            'name' => 'Arenas Blancas',
            'active' => true,
        ]);
        $producer = Producer::query()->create([
            'code' => 'PRO-001',
            'full_name' => 'María López',
            'phone' => '8888-0101',
            'active' => true,
        ]);
        ProducerRouteAssignment::query()->create([
            'producer_id' => $producer->id,
            'route_id' => $route->id,
            'assigned_at' => now()->subDay()->toDateString(),
            'assigned_by' => $user->id,
        ]);
        RouteOperatorAssignment::query()->create([
            'route_id' => $route->id,
            'user_id' => $user->id,
            'assigned_date' => now()->addDay()->toDateString(),
            'assigned_by' => $user->id,
        ]);

        $device = Device::query()->create([
            'user_id' => $user->id,
            'device_uuid' => '10000000-0000-4000-8000-000000000010',
            'name' => 'Equipo de prueba',
            'platform' => 'android',
            'active' => true,
        ]);
        $token = $user->createToken('device:'.$device->device_uuid, ['offline:sync'], now()->addDay())->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.offline.journey', [
                'date' => now()->addDay()->toDateString(),
                'device_uuid' => $device->device_uuid,
            ]))
            ->assertOk()
            ->assertJsonPath('routes.0.id', $route->id)
            ->assertJsonPath('routes.0.producers.0.id', $producer->id)
            ->assertJsonPath('routes.0.producers.0.sequence', 1)
            ->assertJsonStructure(['date', 'manifest_version', 'downloaded_at']);
    }

    public function test_journey_requires_authentication(): void
    {
        $this->getJson(route('api.offline.journey', [
            'date' => now()->addDay()->toDateString(),
            'device_uuid' => '10000000-0000-4000-8000-000000000099',
        ]))
            ->assertUnauthorized();
    }

    public function test_journey_does_not_expose_unassigned_company_routes(): void
    {
        $user = User::factory()->create(['active' => true]);
        $device = Device::query()->create([
            'user_id' => $user->id,
            'device_uuid' => '10000000-0000-4000-8000-000000000011',
            'name' => 'Equipo de prueba',
            'platform' => 'android',
            'active' => true,
        ]);
        $token = $user->createToken('device:'.$device->device_uuid, ['offline:sync'], now()->addDay())->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.offline.journey', [
                'date' => now()->toDateString(),
                'device_uuid' => $device->device_uuid,
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'routes');
    }

    public function test_inactive_device_cannot_download_journey(): void
    {
        $user = User::factory()->create(['active' => true]);
        $device = Device::query()->create([
            'user_id' => $user->id,
            'device_uuid' => '10000000-0000-4000-8000-000000000012',
            'name' => 'Equipo revocado',
            'platform' => 'android',
            'active' => false,
        ]);
        $token = $user->createToken('device:'.$device->device_uuid, ['offline:sync'], now()->addDay())->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.offline.journey', [
                'date' => now()->toDateString(),
                'device_uuid' => $device->device_uuid,
            ]))
            ->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_journey_requires_bound_device_uuid(): void
    {
        $user = User::factory()->create(['active' => true]);
        $device = Device::query()->create([
            'user_id' => $user->id,
            'device_uuid' => '10000000-0000-4000-8000-000000000013',
            'name' => 'Equipo vinculado',
            'platform' => 'android',
            'active' => true,
        ]);
        $token = $user->createToken('device:'.$device->device_uuid, ['offline:sync'], now()->addDay())->plainTextToken;

        $this->withToken($token)
            ->getJson(route('api.offline.journey', ['date' => now()->toDateString()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('device_uuid');
    }
}
