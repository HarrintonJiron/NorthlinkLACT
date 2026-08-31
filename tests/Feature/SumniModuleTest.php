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
        $this->createRutero($norte, ['owner_name' => 'Mario Palacios']);
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
        $this->createRutero($norte, ['owner_name' => 'Mario Palacios']);
        $sur = $this->createRoute(['name' => 'Ruta Sur']);
        $this->createRutero($sur, ['owner_name' => 'Yadira Cano']);
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
                ->where('clients.0.today_density', 25)
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
            'temperature' => 25,
        ])
            ->assertRedirect('/sumni/'.$route->id.'?voucher='.$producer->id)
            ->assertSessionHas('success');

        $this->assertTrue(
            MilkCollection::query()
                ->where('producer_id', $producer->id)
                ->where('route_id', $route->id)
                ->whereDate('collection_date', now()->toDateString())
                ->where('liters', 18.5)
                ->where('temperature', 25)
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
            'temperature' => 25,
        ])->assertRedirect('/sumni/'.$norte->id)->assertSessionHas('error');

        $this->assertSame(0, MilkCollection::query()->count());
    }

    public function test_sumni_rejects_editing_liters_already_recorded_today(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $this->collectMilk($producer, 10);

        $this->from('/sumni/'.$route->id)->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 25,
            'temperature' => 26,
        ])->assertRedirect('/sumni/'.$route->id)->assertSessionHas('error');

        $this->assertEquals(10, MilkCollection::query()->where('producer_id', $producer->id)->value('liters'));
    }

    public function test_sumni_creates_producer_on_route_and_opens_for_liters(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Norte']);

        $response = $this->post('/sumni/'.$route->id.'/producers', [
            'full_name' => 'Cliente Nuevo',
            'identity_number' => '441-99999-0009Z',
            'phone' => '8888-9999',
        ]);

        $response->assertSessionHas('success');

        $producer = \App\Modules\Producers\Models\Producer::query()
            ->where('full_name', 'Cliente Nuevo')
            ->first();

        $response->assertRedirect('/sumni/'.$route->id.'?select='.$producer->id);

        $this->assertDatabaseHas('producer_route_assignments', [
            'route_id' => $route->id,
            'producer_id' => $producer->id,
        ]);

        $this->get('/sumni/'.$route->id.'?select='.$producer->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('clients', 1)
                ->where('clients.0.full_name', 'Cliente Nuevo')
                ->where('clients.0.recorded_today', false)
                ->where('selectProducerId', $producer->id)
            );

        $report = app(ProducerService::class)->weeklyPayroll($route->id);
        $this->assertSame('Cliente Nuevo', collect($report['rows'])->first()['full_name']);
    }

    public function test_sumni_creates_producer_without_identity_number(): void
    {
        $route = $this->createRoute();

        $this->post('/sumni/'.$route->id.'/producers', [
            'full_name' => 'Cliente Sin Cédula',
            'phone' => '8777-0000',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('producers', [
            'full_name' => 'Cliente Sin Cédula',
            'identity_number' => null,
            'phone' => '8777-0000',
        ]);
    }

    public function test_sumni_rejects_zero_negative_and_excessive_liters(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 0,
            'temperature' => 25,
        ])->assertRedirect($url)->assertSessionHasErrors('liters');

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => -3,
            'temperature' => 25,
        ])->assertRedirect($url)->assertSessionHasErrors('liters');

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 10001,
            'temperature' => 25,
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
            'temperature' => 25,
        ])->assertRedirect($url)->assertSessionHasErrors('producer_id');

        $this->from($url)->post($url, [
            'producer_id' => $unassigned->id,
            'liters' => 10,
            'temperature' => 25,
        ])->assertRedirect($url)->assertSessionHas('error');

        $this->from($url)->post($url, [
            'producer_id' => 999999,
            'liters' => 10,
            'temperature' => 25,
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
            'temperature' => 25,
        ])->assertRedirect($url)->assertSessionHasErrors('producer_id');
    }

    public function test_sumni_requires_producer_liters_and_density(): void
    {
        $route = $this->createRoute();
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [])
            ->assertRedirect($url)
            ->assertSessionHasErrors(['producer_id', 'liters', 'temperature']);
    }

    public function test_sumni_stores_density_with_collection(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 14,
            'temperature' => 23.5,
        ])->assertRedirect('/sumni/'.$route->id.'?voucher='.$producer->id);

        $this->assertSame('23.50', MilkCollection::query()->where('producer_id', $producer->id)->value('temperature'));

        $this->get('/sumni/'.$route->id.'?voucher='.$producer->id)
            ->assertInertia(fn (Assert $page) => $page
                ->where('clients.0.today_density', 23.5)
                ->where('lastRecordedProducerId', $producer->id)
                ->where('clients.0.recorded_today', true)
            );
    }

    public function test_sumni_boundary_density_values_are_accepted(): void
    {
        $route = $this->createRoute();
        $low = $this->createProducer($route, ['full_name' => 'Densidad Baja']);
        $high = $this->createProducer($route, ['full_name' => 'Densidad Alta']);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $low->id,
            'liters' => 10,
            'temperature' => 0,
        ])->assertRedirect('/sumni/'.$route->id.'?voucher='.$low->id);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $high->id,
            'liters' => 11,
            'temperature' => 50,
        ])->assertRedirect('/sumni/'.$route->id.'?voucher='.$high->id);

        $this->assertEquals(0, (float) MilkCollection::query()->where('producer_id', $low->id)->value('temperature'));
        $this->assertEquals(50, (float) MilkCollection::query()->where('producer_id', $high->id)->value('temperature'));
    }
}
