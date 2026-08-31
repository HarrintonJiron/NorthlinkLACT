<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class RouteModuleTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_routes_index_renders_stats_and_next_code(): void
    {
        $this->createRoute(['code' => 'RUT-0007', 'name' => 'Ruta Siete']);

        $this->get('/routes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Routes/Index')
                ->has('routes', 1)
                ->where('nextCode', 'RUT-0008')
                ->where('stats.total', 1)
                ->where('stats.active', 1)
            );
    }

    public function test_store_route_generates_code_and_only_requires_name(): void
    {
        $this->from('/routes')->post('/routes', [])
            ->assertRedirect('/routes')
            ->assertSessionHasErrors('name');

        $this->post('/routes', [
            'name' => 'Ruta Jinotega',
        ])->assertRedirect(route('routes.index'))->assertSessionHas('success');

        $route = Route::query()->where('name', 'Ruta Jinotega')->first();
        $this->assertNotNull($route);
        $this->assertSame('RUT-0001', $route->code);
        $this->assertTrue($route->active);
        $this->assertNull($route->rutero);
    }

    public function test_show_route_includes_available_ruteros(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Show']);
        $this->createRutero(null, ['owner_name' => 'Rutero Libre']);

        $this->get('/routes/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Routes/Show')
                ->where('route.name', 'Ruta Show')
                ->has('availableRuteros', 1)
            );
    }

    public function test_update_and_toggle_route(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Vieja']);

        $this->put('/routes/'.$route->id, [
            'name' => 'Ruta Nueva',
        ])->assertRedirect(route('routes.show', $route))->assertSessionHas('success');

        $this->assertSame('Ruta Nueva', $route->fresh()->name);

        $this->patch('/routes/'.$route->id.'/toggle')->assertRedirect();
        $this->assertFalse($route->fresh()->active);

        $this->patch('/routes/'.$route->id.'/toggle')->assertRedirect();
        $this->assertTrue($route->fresh()->active);
    }

    public function test_route_collection_writes_the_same_milk_collection_used_by_sumni(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);

        $this->post('/routes/'.$route->id.'/collections', [
            'producer_id' => $producer->id,
            'collection_date' => now()->toDateString(),
            'liters' => 16,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(
            MilkCollection::query()
                ->where('producer_id', $producer->id)
                ->where('route_id', $route->id)
                ->whereDate('collection_date', now()->toDateString())
                ->where('liters', 16)
                ->exists()
        );

        $this->get('/sumni/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sumni/Show')
                ->where('clients.0.today_liters', 16)
            );
    }

    public function test_route_show_lists_available_ruteros_and_assigns_one_to_the_route(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Asignable']);
        $assigned = $this->createRutero(null, ['owner_name' => 'Mario Palacios']);
        $this->createRutero(null, ['owner_name' => 'Yadira Cano']);

        $this->get('/routes/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Routes/Show')
                ->has('availableRuteros', 2)
            );

        $this->post('/routes/'.$route->id.'/assign-rutero', [
            'rutero_id' => $assigned->id,
        ])->assertRedirect(route('routes.show', $route))->assertSessionHas('success');

        $this->assertSame($route->id, $assigned->fresh()->route_id);
    }

    public function test_route_rejects_assigning_rutero_already_on_another_route(): void
    {
        $norte = $this->createRoute(['name' => 'Norte']);
        $sur = $this->createRoute(['name' => 'Sur']);
        $rutero = $this->createRutero($norte);

        $this->from('/routes/'.$sur->id)->post('/routes/'.$sur->id.'/assign-rutero', [
            'rutero_id' => $rutero->id,
        ])->assertRedirect('/routes/'.$sur->id)->assertSessionHas('error');

        $this->assertSame($norte->id, $rutero->fresh()->route_id);
    }

    public function test_route_can_unassign_rutero(): void
    {
        $route = $this->createRoute();
        $rutero = $this->createRutero($route);

        $this->delete('/routes/'.$route->id.'/rutero')
            ->assertRedirect(route('routes.show', $route))
            ->assertSessionHas('success');

        $this->assertNull($rutero->fresh()->route_id);
    }
}
