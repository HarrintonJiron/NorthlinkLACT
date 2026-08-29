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

    public function test_show_route_includes_assigned_producers_and_today_liters(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Show']);
        $producer = $this->createProducer($route, ['full_name' => 'Cliente de show']);
        $this->collectMilk($producer, 14.25);

        $this->get('/routes/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Routes/Show')
                ->where('today', now()->toDateString())
                ->has('route.active_assignments', 1)
            );

        $this->assertEquals(14.25, MilkCollection::query()->where('producer_id', $producer->id)->value('liters'));
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
}
