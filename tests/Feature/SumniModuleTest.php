<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Services\ProducerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class SumniModuleTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_sumni_index_lists_active_routes_with_their_owners(): void
    {
        $norte = $this->createRoute([
            'name' => 'Ruta Norte',
        ]);
        $this->createRutero($norte, ['full_name' => 'Mario Palacios']);
        $this->createRoute([
            'name' => 'Ruta Inactiva',
            'active' => false,
        ]);
        $visible = $this->createProducer($norte, ['full_name' => 'Ana Visible']);
        $this->collectMilk($visible, 12.5);

        $this->get('/sumni')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sumni/Index')
                ->where('today', now()->toDateString())
                ->has('routes', 1)
                ->where('routes.0.name', 'Ruta Norte')
                ->where('routes.0.owner_name', 'Mario Palacios')
                ->where('routes.0.clients', 1)
                ->where('routes.0.today_visits', 1)
                ->where('routes.0.today_liters', 12.5)
                ->missing('clients')
            );
    }

    public function test_sumni_show_lists_only_clients_of_that_route(): void
    {
        $norte = $this->createRoute(['name' => 'Ruta Norte']);
        $this->createRutero($norte, ['full_name' => 'Mario Palacios']);
        $sur = $this->createRoute(['name' => 'Ruta Sur']);
        $this->createRutero($sur, ['full_name' => 'Yadira Cano']);
        $visible = $this->createProducer($norte, ['full_name' => 'Ana Visible']);
        $this->createProducer($sur, ['full_name' => 'Carlos Sur']);
        $this->createProducer($norte, ['full_name' => 'Pedro Inactivo', 'active' => false]);
        $this->createProducer(null, ['full_name' => 'Luis Sin Ruta']);
        $this->collectMilk($visible, 12.5);

        $this->get('/sumni/'.$norte->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sumni/Show')
                ->where('route.name', 'Ruta Norte')
                ->where('route.owner_name', 'Mario Palacios')
                ->has('clients', 1)
                ->where('clients.0.full_name', 'Ana Visible')
                ->where('clients.0.today_liters', 12.5)
            );
    }

    public function test_sumni_hides_inactive_routes(): void
    {
        $route = $this->createRoute(['active' => false]);

        $this->get('/sumni/'.$route->id)->assertNotFound();
    }

    public function test_sumni_stores_liters_for_today_and_shows_them_on_producer_payroll(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'María Castillo']);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 18.5,
        ])
            ->assertRedirect(route('sumni.show', $route))
            ->assertSessionHas('success');

        $this->assertTrue(
            MilkCollection::query()
                ->where('producer_id', $producer->id)
                ->where('route_id', $route->id)
                ->whereDate('collection_date', now()->toDateString())
                ->where('liters', 18.5)
                ->exists()
        );

        $report = app(ProducerService::class)->weeklyPayroll($route->id);
        $row = collect($report['rows'])->firstWhere('id', $producer->id);

        $this->assertNotNull($row);
        $this->assertSame(18.5, $row['daily'][now()->toDateString()]);
        $this->assertSame(18.5, $row['liters']);
        $this->assertSame(370.0, $row['amount']);
    }

    public function test_sumni_rejects_clients_from_another_route(): void
    {
        $norte = $this->createRoute();
        $sur = $this->createRoute();
        $foreign = $this->createProducer($sur);

        $this->from('/sumni/'.$norte->id)->post('/sumni/'.$norte->id, [
            'producer_id' => $foreign->id,
            'liters' => 10,
        ])->assertRedirect('/sumni/'.$norte->id)->assertSessionHas('error');

        $this->assertSame(0, MilkCollection::query()->count());
    }

    public function test_sumni_replaces_existing_liters_for_the_same_day(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $this->collectMilk($producer, 10);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 25,
        ])->assertRedirect(route('sumni.show', $route));

        $this->assertSame(1, MilkCollection::query()->where('producer_id', $producer->id)->count());
        $this->assertEquals(25, MilkCollection::query()->where('producer_id', $producer->id)->value('liters'));
    }

    public function test_sumni_rejects_zero_negative_and_excessive_liters(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 0,
        ])->assertRedirect($url)->assertSessionHasErrors('liters');

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => -3,
        ])->assertRedirect($url)->assertSessionHasErrors('liters');

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 10001,
        ])->assertRedirect($url)->assertSessionHasErrors('liters');
    }

    public function test_sumni_rejects_inactive_or_unassigned_or_unknown_clients(): void
    {
        $route = $this->createRoute();
        $inactive = $this->createProducer($route, ['active' => false]);
        $unassigned = $this->createProducer(null);
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [
            'producer_id' => $inactive->id,
            'liters' => 10,
        ])->assertRedirect($url)->assertSessionHasErrors('producer_id');

        $this->from($url)->post($url, [
            'producer_id' => $unassigned->id,
            'liters' => 10,
        ])->assertRedirect($url)->assertSessionHas('error');

        $this->from($url)->post($url, [
            'producer_id' => 999999,
            'liters' => 10,
        ])->assertRedirect($url)->assertSessionHasErrors('producer_id');
    }

    public function test_sumni_rejects_soft_deleted_clients(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $producer->delete();
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 10,
        ])->assertRedirect($url)->assertSessionHasErrors('producer_id');
    }

    public function test_sumni_requires_producer_and_liters(): void
    {
        $route = $this->createRoute();
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['producer_id', 'liters']);
    }
}
