<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class ProducerModuleTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_producers_index_shows_weekly_payroll_from_sumni_collections(): void
    {
        $this->createPrice(18.5);
        $norte = $this->createRoute(['name' => 'Norte']);
        $sur = $this->createRoute(['name' => 'Sur']);
        $maria = $this->createProducer($norte, ['full_name' => 'María Elena']);
        $carlos = $this->createProducer($sur, ['full_name' => 'Carlos Méndez']);

        $this->collectMilk($maria, 20, now()->toDateString());
        $this->collectMilk($carlos, 8, now()->toDateString());

        $this->get('/producers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Producers/Index')
                ->has('report.rows', 2)
                ->where('report.totals.liters', 28)
                ->where('report.totals.amount', 518)
            );

        $this->get('/producers?route_id='.$norte->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('report.rows', 1)
                ->where('report.rows.0.full_name', 'María Elena')
                ->where('report.rows.0.liters', 20)
            );
    }

    public function test_weekly_payroll_only_includes_current_friday_to_thursday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-29'));

        $this->createPrice(10);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'José López']);

        $this->collectMilk($producer, 5, '2026-08-27');
        $this->collectMilk($producer, 11, '2026-08-28');
        $this->collectMilk($producer, 7, '2026-08-29');
        $this->collectMilk($producer, 9, '2026-09-03');
        $this->collectMilk($producer, 4, '2026-09-04');

        $this->get('/producers?week=2026-09-03')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.week.start', '2026-08-28')
                ->where('report.week.end', '2026-09-03')
                ->where('report.rows.0.daily.2026-08-28', 11)
                ->where('report.rows.0.daily.2026-08-29', 7)
                ->where('report.rows.0.daily.2026-09-03', 9)
                ->where('report.rows.0.liters', 27)
            );

        Carbon::setTestNow();
    }

    public function test_store_producer_requires_route_and_valid_unique_identity(): void
    {
        $route = $this->createRoute();
        $this->createProducer($route, ['identity_number' => '001-12345-0001A']);

        $this->from('/producers')->post('/producers', [
            'full_name' => 'Sin ruta',
        ])->assertRedirect('/producers')->assertSessionHasErrors('route_id');

        $this->from('/producers')->post('/producers', [
            'full_name' => 'Cédula mala',
            'identity_number' => '123',
            'route_id' => $route->id,
        ])->assertRedirect('/producers')->assertSessionHasErrors('identity_number');

        $this->from('/producers')->post('/producers', [
            'full_name' => 'Cédula repetida',
            'identity_number' => '001-12345-0001A',
            'route_id' => $route->id,
        ])->assertRedirect('/producers')->assertSessionHasErrors('identity_number');
    }

    public function test_store_producer_generates_code_and_assigns_route(): void
    {
        $route = $this->createRoute();

        $this->post('/producers', [
            'full_name' => 'Rosa Gutiérrez',
            'identity_number' => '441-11223-0004C',
            'route_id' => $route->id,
        ])->assertRedirect(route('producers.index'))->assertSessionHas('success');

        $producer = Producer::query()->where('full_name', 'Rosa Gutiérrez')->first();

        $this->assertNotNull($producer);
        $this->assertNotEmpty($producer->code);
        $this->assertTrue(str_starts_with($producer->code, 'PRO-'));
        $this->assertTrue($producer->activeAssignment()->where('route_id', $route->id)->exists());
    }

    public function test_store_producer_can_return_to_the_route_page(): void
    {
        $route = $this->createRoute();

        $this->post('/producers', [
            'full_name' => 'Cliente de ruta',
            'identity_number' => '441-22222-0002B',
            'route_id' => $route->id,
            'return_to' => '/routes/'.$route->id,
        ])->assertRedirect('/routes/'.$route->id);
    }

    public function test_update_producer_can_move_to_another_route(): void
    {
        $norte = $this->createRoute(['name' => 'Norte']);
        $sur = $this->createRoute(['name' => 'Sur']);
        $producer = $this->createProducer($norte, [
            'full_name' => 'Ana Lucía',
            'identity_number' => '441-33333-0003C',
        ]);

        $this->put('/producers/'.$producer->id, [
            'full_name' => 'Ana Lucía',
            'identity_number' => '441-33333-0003C',
            'code' => $producer->code,
            'route_id' => $sur->id,
            'active' => true,
        ])->assertRedirect(route('producers.index'));

        $producer->refresh();
        $this->assertSame($sur->id, $producer->activeAssignment->route_id);
        $this->assertSame(1, $producer->assignments()->whereNull('ended_at')->count());
    }

    public function test_update_producer_can_return_to_original_route_the_same_day(): void
    {
        $norte = $this->createRoute();
        $sur = $this->createRoute();
        $producer = $this->createProducer($norte, [
            'full_name' => 'Ida y vuelta',
            'identity_number' => '441-44444-0004D',
        ]);

        $payload = [
            'full_name' => 'Ida y vuelta',
            'identity_number' => '441-44444-0004D',
            'code' => $producer->code,
            'active' => true,
        ];

        $this->put('/producers/'.$producer->id, [...$payload, 'route_id' => $sur->id])->assertRedirect();
        $this->put('/producers/'.$producer->id, [...$payload, 'route_id' => $norte->id])->assertRedirect();

        $producer->refresh();
        $this->assertSame($norte->id, $producer->activeAssignment->route_id);
        $this->assertSame(1, ProducerRouteAssignment::query()->where('producer_id', $producer->id)->whereNull('ended_at')->count());
    }

    public function test_destroy_soft_deletes_producer_and_hides_them_from_sumni(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'Para borrar']);

        $this->delete('/producers/'.$producer->id)
            ->assertRedirect(route('producers.index'));

        $this->assertSoftDeleted('producers', ['id' => $producer->id]);

        $this->get('/sumni/'.$route->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('clients', 0));
    }
}
