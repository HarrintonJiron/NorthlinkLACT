<?php

namespace Tests\Feature;

use App\Models\RouteOperatorAssignment;
use App\Models\User;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Ruteros\Models\Rutero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class QuebarPilotSeederTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_reset_requires_explicit_confirmation(): void
    {
        $this->artisan('acopio:reset-pilot')
            ->expectsOutputToContain('Operación cancelada')
            ->assertFailed();
    }

    public function test_reset_replaces_acopio_data_with_official_pilot_routes(): void
    {
        $oldRoute = $this->createRoute(['code' => 'TEST-OLD', 'name' => 'Ruta vieja']);
        $this->createProducer($oldRoute, ['code' => 'TEST-PRODUCER']);

        $this->artisan('acopio:reset-pilot', ['--confirm' => 'REINICIAR-ACOPIO'])
            ->expectsOutputToContain('Reinicio completado')
            ->assertSuccessful();

        $this->assertSame(18, Route::query()->count());
        $this->assertSame(527, Producer::query()->count());
        $this->assertSame(527, ProducerRouteAssignment::query()->count());
        $this->assertSame(18, Rutero::query()->count());
        $this->assertSame(17, User::query()->where('username', 'like', 'rutero%')->count());
        $this->assertSame(34, RouteOperatorAssignment::query()->count());

        $this->assertDatabaseHas('routes', ['code' => 'QBR-01', 'name' => 'Dipina']);
        $this->assertDatabaseHas('routes', ['code' => 'QBR-18', 'name' => 'Palestina']);
        $this->assertDatabaseHas('producers', ['payment_method' => 'transfer']);
        $this->assertDatabaseMissing('routes', ['code' => 'TEST-OLD']);
        $this->assertDatabaseMissing('producers', ['code' => 'TEST-PRODUCER']);
    }

    public function test_source_has_no_duplicate_codes_or_names_within_a_route(): void
    {
        $source = json_decode(
            (string) file_get_contents(database_path('data/quebar_pilot_routes.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertCount(18, $source);
        $this->assertSame(527, collect($source)->sum(fn (array $route): int => count($route['producers'])));
        $this->assertCount(18, collect($source)->pluck('code')->unique());

        foreach ($source as $route) {
            $names = collect($route['producers'])
                ->pluck('name')
                ->map(fn (string $name): string => mb_strtolower(trim($name)));

            $this->assertCount($names->count(), $names->unique(), "La ruta {$route['name']} contiene nombres duplicados.");
        }
    }

    public function test_seeded_rutero_can_login_and_download_its_complete_route(): void
    {
        $this->artisan('acopio:reset-pilot', ['--confirm' => 'REINICIAR-ACOPIO'])->assertSuccessful();
        $deviceUuid = '70000000-0000-4000-8000-000000000001';

        $login = $this->postJson(route('api.offline.login'), [
            'username' => 'rutero01',
            'password' => 'RuteroNorthlink2026!',
            'device_uuid' => $deviceUuid,
            'device_name' => 'SUNMI piloto 01',
            'platform' => 'android',
        ])->assertOk();

        Auth::guard('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->withToken($login->json('token'))
            ->getJson(route('api.offline.journey', [
                'date' => today()->toDateString(),
                'device_uuid' => $deviceUuid,
            ]))
            ->assertOk()
            ->assertJsonPath('routes.0.code', 'QBR-01')
            ->assertJsonPath('routes.0.name', 'Dipina')
            ->assertJsonCount(32, 'routes.0.producers');
    }

    public function test_bethel_kubaly_can_be_shared_with_every_rutero_without_replacing_primary_routes(): void
    {
        $this->artisan('acopio:reset-pilot', ['--confirm' => 'REINICIAR-ACOPIO'])->assertSuccessful();

        $this->artisan('acopio:share-route', ['route' => 'QBR-05'])
            ->expectsOutputToContain('Bethel Kubaly agregada a 17 ruteros')
            ->assertSuccessful();

        $bethel = Route::query()->where('code', 'QBR-05')->firstOrFail();
        $rutero11 = User::query()->where('username', 'rutero11')->firstOrFail();

        $this->assertSame(17, RouteOperatorAssignment::query()
            ->where('route_id', $bethel->id)
            ->whereDate('assigned_date', today())
            ->count());
        $this->assertSame(2, RouteOperatorAssignment::query()
            ->where('user_id', $rutero11->id)
            ->whereDate('assigned_date', today())
            ->count());
        $rutero11Bethel = RouteOperatorAssignment::query()
            ->where('route_id', $bethel->id)
            ->where('user_id', $rutero11->id)
            ->whereDate('assigned_date', today())
            ->firstOrFail();
        $this->assertSame('PIL-11', $rutero11Bethel->vehicle_plate);
        $this->assertTrue($rutero11Bethel->is_shared_test);

        $deviceUuid = '70000000-0000-4000-8000-000000000011';
        $login = $this->postJson(route('api.offline.login'), [
            'username' => 'rutero11',
            'password' => 'RuteroNorthlink2026!',
            'device_uuid' => $deviceUuid,
            'device_name' => 'SUNMI piloto 11',
            'platform' => 'android',
        ])->assertOk();

        Auth::guard('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $response = $this->withToken($login->json('token'))
            ->getJson(route('api.offline.journey', [
                'date' => today()->toDateString(),
                'device_uuid' => $deviceUuid,
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'routes');

        $bethelManifest = collect($response->json('routes'))->firstWhere('code', 'QBR-05');
        $this->assertSame('PIL-11', $bethelManifest['vehicle_plate']);
    }
}
