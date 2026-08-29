<?php

namespace Tests\Feature;

use App\Modules\Ruteros\Models\Rutero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class RuteroModuleTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_ruteros_index_lists_owners_and_available_routes(): void
    {
        $norte = $this->createRoute(['name' => 'Ruta Norte']);
        $this->createRoute(['name' => 'Ruta Libre']);
        $this->createRutero($norte, ['full_name' => 'Mario Palacios']);

        $this->get('/ruteros')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ruteros/Index')
                ->has('ruteros', 1)
                ->where('ruteros.0.full_name', 'Mario Palacios')
                ->has('availableRoutes', 1)
                ->where('availableRoutes.0.name', 'Ruta Libre')
                ->where('stats.total', 1)
            );
    }

    public function test_store_rutero_requires_owner_fields_and_a_free_route(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Jinotega']);

        $this->from('/ruteros')->post('/ruteros', [
            'full_name' => 'Mario',
        ])->assertRedirect('/ruteros')->assertSessionHasErrors([
            'identity_number',
            'phone',
            'vehicle_plate',
            'route_id',
        ]);

        $this->post('/ruteros', [
            'full_name' => 'Mario Palacios',
            'identity_number' => '441-01010-0001A',
            'phone' => '8888-1010',
            'vehicle_plate' => 'M-1010',
            'route_id' => $route->id,
        ])->assertRedirect(route('ruteros.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('ruteros', [
            'full_name' => 'Mario Palacios',
            'route_id' => $route->id,
        ]);
    }

    public function test_store_rutero_rejects_duplicate_route_and_identity(): void
    {
        $route = $this->createRoute();
        $other = $this->createRoute();
        $this->createRutero($route, [
            'full_name' => 'Mario Palacios',
            'identity_number' => '441-01010-0001A',
        ]);

        $this->from('/ruteros')->post('/ruteros', [
            'full_name' => 'Otro',
            'identity_number' => '441-01010-0001A',
            'phone' => '8777-2020',
            'vehicle_plate' => 'JI-2020',
            'route_id' => $other->id,
        ])->assertRedirect('/ruteros')->assertSessionHasErrors('identity_number');

        $this->from('/ruteros')->post('/ruteros', [
            'full_name' => 'Otro',
            'identity_number' => '441-02020-0002B',
            'phone' => '8777-2020',
            'vehicle_plate' => 'JI-2020',
            'route_id' => $route->id,
        ])->assertRedirect('/ruteros')->assertSessionHasErrors('route_id');
    }

    public function test_update_and_toggle_rutero(): void
    {
        $norte = $this->createRoute(['name' => 'Norte']);
        $sur = $this->createRoute(['name' => 'Sur']);
        $rutero = $this->createRutero($norte, [
            'full_name' => 'Mario Palacios',
            'identity_number' => '441-01010-0001A',
            'phone' => '8888-1010',
            'vehicle_plate' => 'M-1010',
        ]);

        $this->put('/ruteros/'.$rutero->id, [
            'full_name' => 'Mario Palacios Ruiz',
            'identity_number' => '441-01010-0001A',
            'phone' => '8888-9999',
            'vehicle_plate' => 'M-9999',
            'route_id' => $sur->id,
        ])->assertRedirect(route('ruteros.show', $rutero))->assertSessionHas('success');

        $this->assertSame('Mario Palacios Ruiz', $rutero->fresh()->full_name);
        $this->assertSame($sur->id, $rutero->fresh()->route_id);

        $this->patch('/ruteros/'.$rutero->id.'/toggle')->assertRedirect();
        $this->assertFalse($rutero->fresh()->active);
    }

    public function test_sumni_reads_owner_from_the_rutero(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Norte']);
        $this->createRutero($route, ['full_name' => 'Yadira Cano', 'vehicle_plate' => 'JI-2020']);

        $this->get('/sumni/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.owner_name', 'Yadira Cano')
                ->where('route.vehicle_plate', 'JI-2020')
            );
    }
}
