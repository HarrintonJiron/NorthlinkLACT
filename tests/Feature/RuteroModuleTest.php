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

    public function test_ruteros_index_lists_owners_without_requiring_route_assignment(): void
    {
        $this->createRutero(null, ['owner_name' => 'Mario Palacios']);

        $this->get('/ruteros')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ruteros/Index')
                ->has('ruteros', 1)
                ->where('ruteros.0.owner_name', 'Mario Palacios')
                ->where('ruteros.0.route', null)
                ->where('stats.total', 1)
            );
    }

    public function test_store_rutero_requires_owner_and_driver_fields_but_not_route(): void
    {
        $this->from('/ruteros')->post('/ruteros', [
            'owner_name' => 'Mario',
        ])->assertRedirect('/ruteros')->assertSessionHasErrors([
            'owner_identity_number',
            'owner_phone',
            'vehicle_description',
            'vehicle_plate',
            'driver_name',
            'driver_identity_number',
            'driver_phone',
        ]);

        $this->post('/ruteros', [
            'owner_name' => 'Mario Palacios',
            'owner_identity_number' => '441-01010-0001A',
            'owner_phone' => '8888-1010',
            'vehicle_description' => 'Isuzu NPR',
            'vehicle_plate' => 'M-1010',
            'driver_name' => 'Carlos Rivas',
            'driver_identity_number' => '441-01011-0001B',
            'driver_phone' => '8855-1010',
        ])->assertRedirect(route('ruteros.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('ruteros', [
            'owner_name' => 'Mario Palacios',
            'driver_name' => 'Carlos Rivas',
            'route_id' => null,
        ]);
    }

    public function test_store_rutero_rejects_duplicate_owner_identity(): void
    {
        $this->createRutero(null, [
            'owner_name' => 'Mario Palacios',
            'owner_identity_number' => '441-01010-0001A',
        ]);

        $this->from('/ruteros')->post('/ruteros', [
            'owner_name' => 'Otro',
            'owner_identity_number' => '441-01010-0001A',
            'owner_phone' => '8777-2020',
            'vehicle_description' => 'Hino 300',
            'vehicle_plate' => 'JI-2020',
            'driver_name' => 'Luis Mejía',
            'driver_identity_number' => '441-02021-0002C',
            'driver_phone' => '8844-2020',
        ])->assertRedirect('/ruteros')->assertSessionHasErrors('owner_identity_number');
    }

    public function test_update_and_toggle_rutero_without_changing_route(): void
    {
        $route = $this->createRoute(['name' => 'Norte']);
        $rutero = $this->createRutero($route, [
            'owner_name' => 'Mario Palacios',
            'owner_identity_number' => '441-01010-0001A',
            'owner_phone' => '8888-1010',
            'vehicle_description' => 'Isuzu NPR',
            'vehicle_plate' => 'M-1010',
            'driver_name' => 'Carlos Rivas',
            'driver_identity_number' => '441-01011-0001B',
            'driver_phone' => '8855-1010',
        ]);

        $this->put('/ruteros/'.$rutero->id, [
            'owner_name' => 'Mario Palacios Ruiz',
            'owner_identity_number' => '441-01010-0001A',
            'owner_phone' => '8888-9999',
            'vehicle_description' => 'Isuzu NPR',
            'vehicle_plate' => 'M-9999',
            'driver_name' => 'Pedro Encargado',
            'driver_identity_number' => '441-01011-0001B',
            'driver_phone' => '8855-9999',
            'return_to' => '/routes/'.$route->id,
        ])->assertRedirect('/routes/'.$route->id)->assertSessionHas('success');

        $this->assertSame('Mario Palacios Ruiz', $rutero->fresh()->owner_name);

        $this->patch('/ruteros/'.$rutero->id.'/toggle')->assertRedirect();
        $this->assertFalse($rutero->fresh()->active);
    }

    public function test_rutero_show_accepts_return_to_route(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Norte']);
        $rutero = $this->createRutero($route);

        $this->get('/ruteros/'.$rutero->id.'?return_to=/routes/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ruteros/Show')
                ->where('returnTo', '/routes/'.$route->id)
            );
    }

    public function test_sumni_reads_owner_and_driver_from_the_rutero(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Norte']);
        $this->createRutero($route, [
            'owner_name' => 'Yadira Cano',
            'driver_name' => 'Luis Mejía',
            'vehicle_plate' => 'JI-2020',
        ]);

        $this->get('/sumni/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('route.owner_name', 'Yadira Cano')
                ->where('route.driver_name', 'Luis Mejía')
                ->where('route.vehicle_plate', 'JI-2020')
            );
    }
}
