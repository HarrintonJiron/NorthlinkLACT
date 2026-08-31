<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Services\ProducerService;
use App\Modules\Ruteros\Models\Rutero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class AcopioFlowTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_full_acopio_flow_from_route_to_payroll_voucher(): void
    {
        $this->createPrice(20);

        $this->post('/routes', ['name' => 'Ruta Completa'])->assertRedirect();
        $route = \App\Modules\Producers\Models\Route::query()->where('name', 'Ruta Completa')->firstOrFail();

        $this->post('/ruteros', [
            'owner_name' => 'Dueño Flujo',
            'owner_identity_number' => '441-70001-0001A',
            'owner_phone' => '8888-7001',
            'vehicle_description' => 'Camión cisterna',
            'vehicle_plate' => 'M-7001',
            'driver_name' => 'Encargado Flujo',
            'driver_identity_number' => '441-70002-0001B',
            'driver_phone' => '8777-7002',
        ])->assertRedirect(route('ruteros.index'));

        $rutero = Rutero::query()->where('owner_name', 'Dueño Flujo')->firstOrFail();
        $this->assertNull($rutero->route_id);

        $this->post('/routes/'.$route->id.'/assign-rutero', [
            'rutero_id' => $rutero->id,
        ])->assertRedirect(route('routes.show', $route));

        $this->assertSame($route->id, $rutero->fresh()->route_id);

        $this->post('/sumni/'.$route->id.'/producers', [
            'full_name' => 'Productor Flujo',
            'phone' => '8855-7003',
        ])->assertRedirect();

        $producer = Producer::query()->where('full_name', 'Productor Flujo')->firstOrFail();
        $this->assertNull($producer->identity_number);
        $this->assertSame($route->id, $producer->activeAssignment->route_id);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 22.5,
            'temperature' => 24.5,
        ])->assertRedirect('/sumni/'.$route->id.'?voucher='.$producer->id);

        $this->assertDatabaseHas('milk_collections', [
            'producer_id' => $producer->id,
            'route_id' => $route->id,
            'liters' => 22.5,
            'temperature' => 24.5,
        ]);

        $this->get('/sumni/'.$route->id.'?voucher='.$producer->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('lastRecordedProducerId', $producer->id)
                ->where('clients.0.recorded_today', true)
                ->where('clients.0.today_density', 24.5)
            );

        $report = app(ProducerService::class)->weeklyPayroll($route->id);
        $row = collect($report['rows'])->firstWhere('id', $producer->id);
        $this->assertNotNull($row);
        $this->assertSame(22.5, $row['liters']);
        $this->assertSame(450.0, $row['amount']);

        $this->get('/producers?route_id='.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.rows.0.full_name', 'Productor Flujo')
            );
    }

    public function test_sumni_rejects_density_out_of_range_and_duplicate_identity(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $url = '/sumni/'.$route->id;

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 10,
            'temperature' => -1,
        ])->assertRedirect($url)->assertSessionHasErrors('temperature');

        $this->from($url)->post($url, [
            'producer_id' => $producer->id,
            'liters' => 10,
            'temperature' => 51,
        ])->assertRedirect($url)->assertSessionHasErrors('temperature');

        $this->createProducer($route, ['identity_number' => '441-88888-0008H']);

        $this->from($url)->post($url.'/producers', [
            'full_name' => 'Duplicado',
            'identity_number' => '441-88888-0008H',
            'phone' => '8888-0000',
        ])->assertRedirect($url)->assertSessionHasErrors('identity_number');
    }

    public function test_route_can_swap_rutero_and_rejects_inactive(): void
    {
        $route = $this->createRoute();
        $current = $this->createRutero($route, ['owner_name' => 'Actual']);
        $replacement = $this->createRutero(null, ['owner_name' => 'Reemplazo']);
        $inactive = $this->createRutero(null, ['owner_name' => 'Inactivo', 'active' => false]);

        $this->post('/routes/'.$route->id.'/assign-rutero', [
            'rutero_id' => $replacement->id,
        ])->assertRedirect(route('routes.show', $route));

        $this->assertNull($current->fresh()->route_id);
        $this->assertSame($route->id, $replacement->fresh()->route_id);

        $this->from('/routes/'.$route->id)->post('/routes/'.$route->id.'/assign-rutero', [
            'rutero_id' => $inactive->id,
        ])->assertRedirect('/routes/'.$route->id)->assertSessionHas('error');

        $this->assertSame($route->id, $replacement->fresh()->route_id);
        $this->assertNull($inactive->fresh()->route_id);
    }

    public function test_immutable_collection_preserves_original_density(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);

        $this->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 15,
            'temperature' => 25,
        ])->assertRedirect();

        $this->from('/sumni/'.$route->id)->post('/sumni/'.$route->id, [
            'producer_id' => $producer->id,
            'liters' => 99,
            'temperature' => 30,
        ])->assertRedirect('/sumni/'.$route->id)->assertSessionHas('error');

        $collection = MilkCollection::query()->where('producer_id', $producer->id)->first();
        $this->assertSame(1, MilkCollection::query()->where('producer_id', $producer->id)->count());
        $this->assertEquals(15, (float) $collection->liters);
        $this->assertEquals(25, (float) $collection->temperature);
    }
}
