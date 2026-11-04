<?php

namespace Tests\Feature;

use App\Models\RouteOperatorAssignment;
use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    private const DEVICE_UUID = '10000000-0000-4000-8000-000000000001';

    private const RUN_UUID = '20000000-0000-4000-8000-000000000001';

    private const COLLECTION_UUID = '30000000-0000-4000-8000-000000000001';

    private User $user;

    private Company $company;

    private Plant $plant;

    private Route $route;

    private Producer $producer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'offline',
            'password' => Hash::make('ClaveOffline2026'),
            'active' => true,
        ]);

        $this->company = Company::query()->create([
            'name' => 'Northlink LACT',
            'legal_name' => 'Northlink LACT',
            'tax_id' => 'J0310000000000',
            'active' => true,
        ]);

        $this->plant = Plant::query()->create([
            'company_id' => $this->company->id,
            'name' => 'Planta Offline',
            'code' => 'PLT-OFF',
            'active' => true,
        ]);

        $this->company->users()->attach($this->user->id, [
            'plant_id' => $this->plant->id,
        ]);

        $this->route = Route::query()->create([
            'company_id' => $this->company->id,
            'plant_id' => $this->plant->id,
            'code' => 'RUT-OFF',
            'name' => 'Ruta Offline',
            'active' => true,
        ]);

        $this->producer = Producer::query()->create([
            'code' => 'PRO-OFF',
            'full_name' => 'Productor Offline',
            'active' => true,
        ]);

        ProducerRouteAssignment::query()->create([
            'producer_id' => $this->producer->id,
            'route_id' => $this->route->id,
            'assigned_at' => '2026-09-01',
            'assigned_by' => $this->user->id,
        ]);

        RouteOperatorAssignment::query()->create([
            'route_id' => $this->route->id,
            'user_id' => $this->user->id,
            'assigned_date' => '2026-09-01',
            'assigned_by' => $this->user->id,
        ]);
    }

    public function test_device_login_issues_sanctum_token_and_registers_uuid(): void
    {
        $this->postJson(route('api.offline.login'), $this->loginPayload())
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('device.uuid', self::DEVICE_UUID)
            ->assertJsonPath('user.username', 'offline')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('devices', [
            'user_id' => $this->user->id,
            'device_uuid' => self::DEVICE_UUID,
            'active' => true,
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotNull($this->user->tokens()->firstOrFail()->expires_at);
    }

    public function test_device_can_rotate_between_active_operator_accounts(): void
    {
        $firstToken = $this->postJson(route('api.offline.login'), $this->loginPayload())
            ->assertOk()
            ->json('token');

        $replacement = User::factory()->create([
            'username' => 'relevo',
            'password' => Hash::make('ClaveRelevo2026'),
            'active' => true,
        ]);

        $this->postJson(route('api.offline.login'), [
            ...$this->loginPayload(),
            'username' => 'relevo',
            'password' => 'ClaveRelevo2026',
        ])
            ->assertOk()
            ->assertJsonPath('user.username', 'relevo');

        $this->assertDatabaseHas('devices', [
            'device_uuid' => self::DEVICE_UUID,
            'user_id' => $replacement->id,
            'active' => true,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'device:'.self::DEVICE_UUID,
        ]);

        $this->withToken($firstToken)
            ->postJson(route('api.offline.sync'), $this->syncPayload())
            ->assertUnauthorized();
    }

    public function test_failed_offline_login_uses_account_lockout_state(): void
    {
        config(['auth_security.lockout.threshold' => 2]);

        $payload = $this->loginPayload();
        $payload['password'] = 'incorrecta';

        $this->postJson(route('api.offline.login'), $payload)->assertUnprocessable();
        $this->postJson(route('api.offline.login'), $payload)->assertUnprocessable();

        $this->assertSame(2, $this->user->fresh()->failed_login_attempts);
        $this->assertNotNull($this->user->fresh()->locked_until);

        $payload['password'] = 'ClaveOffline2026';
        $this->postJson(route('api.offline.login'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_deactivated_device_cannot_reactivate_itself_by_logging_in(): void
    {
        $this->deviceToken();
        $this->user->devices()->update(['active' => false]);

        $this->postJson(route('api.offline.login'), $this->loginPayload())
            ->assertForbidden();

        $this->assertFalse($this->user->devices()->firstOrFail()->active);
    }

    public function test_unauthenticated_device_cannot_sync(): void
    {
        $this->postJson(route('api.offline.sync'), $this->syncPayload())
            ->assertUnauthorized();
    }

    public function test_device_token_cannot_submit_another_device_uuid(): void
    {
        $payload = $this->syncPayload();
        $payload['device_uuid'] = '10000000-0000-4000-8000-000000000099';

        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $payload)
            ->assertForbidden();

        $this->assertDatabaseCount('route_runs', 0);
    }

    public function test_route_run_and_collections_are_synchronized_idempotently(): void
    {
        $token = $this->deviceToken();
        $payload = $this->syncPayload();

        $this->withToken($token)
            ->postJson(route('api.offline.sync'), $payload)
            ->assertOk()
            ->assertJson([
                'sync_status' => 'synced',
                'route_run_uuid' => self::RUN_UUID,
                'created' => 1,
                'already_synced' => 0,
                'conflicts' => [],
            ]);

        $this->withToken($token)
            ->postJson(route('api.offline.sync'), $payload)
            ->assertOk()
            ->assertJson([
                'sync_status' => 'synced',
                'created' => 0,
                'already_synced' => 1,
                'conflicts' => [],
            ]);

        $this->assertDatabaseCount('route_runs', 1);
        $this->assertDatabaseCount('milk_collections', 1);
        $this->assertDatabaseHas('milk_collections', [
            'external_uuid' => self::COLLECTION_UUID,
            'route_id' => $this->route->id,
            'producer_id' => $this->producer->id,
            'sync_status' => 'synced',
        ]);
    }

    public function test_changed_payload_for_existing_collection_uuid_returns_conflict(): void
    {
        $token = $this->deviceToken();
        $payload = $this->syncPayload();

        $this->withToken($token)
            ->postJson(route('api.offline.sync'), $payload)
            ->assertOk();

        $payload['collections'][0]['liters'] = 99;

        $this->withToken($token)
            ->postJson(route('api.offline.sync'), $payload)
            ->assertConflict()
            ->assertJsonPath('sync_status', 'conflict')
            ->assertJsonCount(1, 'conflicts');

        $this->assertDatabaseHas('milk_collections', [
            'external_uuid' => self::COLLECTION_UUID,
            'liters' => 18.50,
        ]);
    }

    public function test_user_cannot_sync_route_from_another_company(): void
    {
        $otherCompany = Company::query()->create([
            'name' => 'Otra empresa',
            'legal_name' => 'Otra empresa',
            'tax_id' => 'J0310000000001',
            'active' => true,
        ]);
        $otherPlant = Plant::query()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Otra planta',
            'code' => 'PLT-EXT',
            'active' => true,
        ]);
        $otherRoute = Route::query()->create([
            'company_id' => $otherCompany->id,
            'plant_id' => $otherPlant->id,
            'code' => 'RUT-EXT',
            'name' => 'Ruta externa',
            'active' => true,
        ]);
        $payload = $this->syncPayload();
        $payload['route_run']['route_id'] = $otherRoute->id;

        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $payload)
            ->assertConflict()
            ->assertJsonPath('sync_status', 'conflict');

        $this->assertDatabaseCount('route_runs', 0);
        $this->assertDatabaseCount('milk_collections', 0);
    }

    public function test_user_cannot_sync_a_route_without_assignment_for_the_run_date(): void
    {
        RouteOperatorAssignment::query()->delete();

        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $this->syncPayload())
            ->assertConflict()
            ->assertJsonPath('message', 'La ruta no está asignada al usuario para esta jornada.');

        $this->assertDatabaseCount('route_runs', 0);
        $this->assertDatabaseCount('milk_collections', 0);
    }

    public function test_collection_date_must_match_route_run_date(): void
    {
        $payload = $this->syncPayload();
        $payload['collections'][0]['collection_date'] = '2026-09-02';

        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('collections.0.collection_date');
    }

    public function test_non_delivery_visit_can_sync_with_zero_liters(): void
    {
        $payload = $this->syncPayload();
        $payload['collections'][0]['visit_status'] = 'ausente';
        $payload['collections'][0]['liters'] = 0;

        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('milk_collections', [
            'external_uuid' => self::COLLECTION_UUID,
            'visit_status' => 'ausente',
            'liters' => 0,
        ]);
    }

    public function test_field_producer_and_collection_are_registered_idempotently(): void
    {
        $token = $this->deviceToken();
        $payload = $this->syncPayload();
        $producerUuid = '40000000-0000-4000-8000-000000000001';
        $payload['new_producers'] = [[
            'external_uuid' => $producerUuid,
            'full_name' => 'Productora registrada en campo',
            'phone' => '8888-1234',
        ]];
        unset($payload['collections'][0]['producer_id']);
        $payload['collections'][0]['producer_external_uuid'] = $producerUuid;
        $payload['collections'][0]['density'] = 1.032;

        $this->withToken($token)->postJson(route('api.offline.sync'), $payload)
            ->assertOk()
            ->assertJsonPath('registered_producers.0.external_uuid', $producerUuid)
            ->assertJsonPath('created', 1);

        $this->withToken($token)->postJson(route('api.offline.sync'), $payload)
            ->assertOk()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('already_synced', 1);

        $producer = Producer::query()->where('external_uuid', $producerUuid)->firstOrFail();
        $this->assertDatabaseCount('producers', 2);
        $this->assertDatabaseHas('producer_route_assignments', [
            'producer_id' => $producer->id,
            'route_id' => $this->route->id,
        ]);
        $this->assertDatabaseHas('milk_collections', [
            'producer_id' => $producer->id,
            'external_uuid' => self::COLLECTION_UUID,
            'density' => 1.032,
        ]);
    }

    public function test_conflicted_batch_rolls_back_field_producer_registration(): void
    {
        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $this->syncPayload())
            ->assertOk();

        $payload = $this->syncPayload();
        $payload['route_run']['uuid'] = '20000000-0000-4000-8000-000000000002';
        $payload['collections'][0]['uuid'] = '30000000-0000-4000-8000-000000000002';
        $producerUuid = '40000000-0000-4000-8000-000000000002';
        $payload['new_producers'] = [[
            'external_uuid' => $producerUuid,
            'full_name' => 'Productor que no debe persistir',
            'phone' => '8888-9999',
        ]];

        $this->withToken($this->deviceToken())
            ->postJson(route('api.offline.sync'), $payload)
            ->assertConflict();

        $this->assertDatabaseMissing('producers', ['external_uuid' => $producerUuid]);
        $this->assertDatabaseCount('producer_route_assignments', 1);
        $this->assertDatabaseCount('milk_collections', 1);
    }

    private function deviceToken(): string
    {
        return $this->postJson(route('api.offline.login'), $this->loginPayload())
            ->assertOk()
            ->json('token');
    }

    /**
     * @return array<string, string>
     */
    private function loginPayload(): array
    {
        return [
            'username' => 'offline',
            'password' => 'ClaveOffline2026',
            'device_uuid' => self::DEVICE_UUID,
            'device_name' => 'Tablet Ruta 1',
            'platform' => 'android',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncPayload(): array
    {
        return [
            'device_uuid' => self::DEVICE_UUID,
            'route_run' => [
                'uuid' => self::RUN_UUID,
                'route_id' => $this->route->id,
                'run_date' => '2026-09-01',
                'status' => 'completed',
                'started_at' => '2026-09-01T06:00:00-06:00',
                'completed_at' => '2026-09-01T12:00:00-06:00',
            ],
            'collections' => [
                [
                    'uuid' => self::COLLECTION_UUID,
                    'producer_id' => $this->producer->id,
                    'collection_date' => '2026-09-01',
                    'liters' => 18.5,
                    'temperature' => 25,
                    'acidity' => 6.8,
                    'fat_percentage' => 3.5,
                    'notes' => 'Capturado sin conexión.',
                ],
            ],
        ];
    }
}
