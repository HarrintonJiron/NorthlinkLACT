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

        $this->assertSame(17, Route::query()->count());
        $this->assertSame(617, Producer::query()->count());
        $this->assertSame(617, ProducerRouteAssignment::query()->count());
        $this->assertSame(17, Rutero::query()->count());
        $this->assertSame(17, User::query()->where('username', 'like', 'rutero%')->count());
        $this->assertSame(34, RouteOperatorAssignment::query()->count());

        $this->assertDatabaseHas('routes', ['code' => 'QBR-01', 'name' => 'Dipina']);
        $this->assertDatabaseHas('routes', ['code' => 'QBR-17', 'name' => 'Los Mangos']);
        $this->assertDatabaseHas('producers', ['code' => 'QBR-01-001', 'full_name' => 'Miguel Ramirez']);
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

        $this->assertCount(17, $source);
        $this->assertSame(617, collect($source)->sum(fn (array $route): int => count($route['producers'])));
        $this->assertCount(17, collect($source)->pluck('code')->unique());

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
            ->assertJsonCount(41, 'routes.0.producers');
    }
}
