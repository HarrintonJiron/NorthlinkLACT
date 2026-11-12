<?php

namespace Tests\Feature;

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\ProducerWeekAdjustment;
use App\Modules\Producers\Services\ProducerService;
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

    public function test_payment_sheets_uses_real_weekly_producer_report(): void
    {
        $this->createPrice(18.5);
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $this->collectMilk($producer, 10, now()->toDateString());

        $this->get(route('payment-sheets.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Producers/Index')
                ->where('mode', 'payment-sheets')
                ->where('report.totals.liters', 10)
                ->where('report.totals.amount', 185));
    }

    public function test_payment_sheet_closure_freezes_values_and_prevents_duplicate_or_adjustment(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'Productor cierre']);
        $this->collectMilk($producer, 10, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();

        $this->post(route('payment-sheets.close'), ['week_end' => $weekEnd])
            ->assertRedirect()
            ->assertSessionHas('success');

        $batch = ProducerPaymentBatch::query()->with('items')->firstOrFail();
        $this->assertSame('closed', $batch->status);
        $this->assertSame('200.00', $batch->net_amount);
        $this->assertSame('10.00', $batch->items->first()->liters);

        $this->from(route('payment-sheets.index'))->post(route('payment-sheets.close'), ['week_end' => $weekEnd])
            ->assertRedirect(route('payment-sheets.index'))
            ->assertSessionHasErrors(['week_end']);

        $this->from(route('payment-sheets.index'))->put(route('producers.week-adjustment', $producer), [
            'week_end' => $weekEnd,
            'advance_amount' => 25,
        ])->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors(['week_end']);
        $this->assertDatabaseMissing('producer_week_adjustments', ['producer_id' => $producer->id]);
    }

    public function test_weekly_price_can_be_changed_before_thursday_closure_and_is_audited(): void
    {
        $this->createPrice(18.5);
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $this->collectMilk($producer, 10, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();

        $this->put(route('payment-sheets.price.update'), [
            'week_end' => $weekEnd,
            'price_per_liter' => 21.75,
            'reason' => 'Precio autorizado por gerencia',
        ])->assertRedirect()->assertSessionHas('success');

        $weeklyPrice = MilkPrice::query()
            ->whereDate('effective_until', $weekEnd)
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertSame('21.7500', $weeklyPrice->price_per_liter);
        $this->assertDatabaseHas('audit_events', [
            'entity_type' => MilkPrice::class,
            'entity_id' => $weeklyPrice->id,
            'user_id' => $this->user->id,
        ]);

        $this->get(route('payment-sheets.index', ['week' => $weekEnd]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.price', 21.75)
                ->where('report.rows.0.amount', 217.5));

        $this->post(route('payment-sheets.close'), ['week_end' => $weekEnd])->assertRedirect();
        $this->from(route('payment-sheets.index'))->put(route('payment-sheets.price.update'), [
            'week_end' => $weekEnd,
            'price_per_liter' => 22,
            'reason' => 'Intento posterior al cierre',
        ])->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors('week_end');
    }

    public function test_weekly_price_requires_a_thursday_and_a_reason(): void
    {
        $this->createPrice();

        $this->from(route('payment-sheets.index'))->put(route('payment-sheets.price.update'), [
            'week_end' => '2026-11-04',
            'price_per_liter' => 20,
            'reason' => 'x',
        ])->assertRedirect(route('payment-sheets.index'))
            ->assertSessionHasErrors(['week_end', 'reason']);
    }

    public function test_closed_payment_sheet_has_an_80mm_producer_ticket(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute(['code' => 'RUT-TICKET']);
        $producer = $this->createProducer($route, ['full_name' => 'Productor del Ticket']);
        $this->collectMilk($producer, 12.5, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();
        $this->post(route('payment-sheets.close'), ['week_end' => $weekEnd])->assertRedirect();
        $batch = ProducerPaymentBatch::query()->firstOrFail();

        $this->get(route('payment-sheets.ticket', [$batch, $producer]))
            ->assertOk()
            ->assertSee('LÁCTEOS QUEBAR')
            ->assertSee('Productor del Ticket')
            ->assertSee('RUT-TICKET')
            ->assertSee('Imprimir 80 mm')
            ->assertSee('@page { size: 80mm auto;', false);

        $otherProducer = $this->createProducer($route);
        $this->get(route('payment-sheets.ticket', [$batch, $otherProducer]))->assertNotFound();
        $this->assertSame(1, AuditEvent::query()->where('entity_type', ProducerPaymentBatch::class)->count());
    }

    public function test_closed_unpaid_sheet_price_can_be_revised_without_overwriting_individual_prices(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute();
        $baseProducer = $this->createProducer($route, ['full_name' => 'Precio base']);
        $adjustedProducer = $this->createProducer($route, ['full_name' => 'Precio individual']);
        $this->collectMilk($baseProducer, 10, now()->toDateString());
        $this->collectMilk($adjustedProducer, 10, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();
        ProducerWeekAdjustment::query()->create([
            'producer_id' => $adjustedProducer->id,
            'week_end' => $weekEnd,
            'density_price' => 15,
            'advance_amount' => 0,
        ]);
        $this->post(route('payment-sheets.close'), ['week_end' => $weekEnd])->assertRedirect();
        $batch = ProducerPaymentBatch::query()->firstOrFail();

        $this->patch(route('payment-sheets.price.revise', $batch), [
            'price_per_liter' => 22,
            'reason' => 'Corrección autorizada por gerencia',
        ])->assertRedirect()->assertSessionHas('success');

        $batch->refresh();
        $this->assertSame('370.00', $batch->gross_amount);
        $this->assertSame('370.00', $batch->net_amount);
        $this->assertDatabaseHas('producer_payment_items', [
            'batch_id' => $batch->id,
            'producer_id' => $baseProducer->id,
            'price_per_liter' => 22,
            'net_amount' => 220,
        ]);
        $this->assertDatabaseHas('producer_payment_items', [
            'batch_id' => $batch->id,
            'producer_id' => $adjustedProducer->id,
            'price_per_liter' => 15,
            'net_amount' => 150,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'entity_type' => ProducerPaymentBatch::class,
            'entity_id' => $batch->id,
            'action' => 'price_revised',
        ]);

        $this->patch(route('payment-sheets.status', $batch), ['status' => 'reviewed'])->assertRedirect();
        $this->patch(route('payment-sheets.status', $batch), ['status' => 'approved'])->assertRedirect();
        $this->patch(route('payment-sheets.pay', $batch))->assertRedirect();
        $this->from(route('payment-sheets.index'))->patch(route('payment-sheets.price.revise', $batch), [
            'price_per_liter' => 23,
            'reason' => 'Intento después del pago',
        ])->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors('batch');
    }

    public function test_paid_payment_sheet_creates_traceable_finance_payment_once(): void
    {
        $this->createPrice(18.5);
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $this->collectMilk($producer, 12, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();
        $this->post(route('payment-sheets.close'), ['week_end' => $weekEnd])->assertRedirect();
        $batch = ProducerPaymentBatch::query()->firstOrFail();

        $this->from(route('payment-sheets.index'))->patch(route('payment-sheets.pay', $batch))
            ->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors(['batch']);

        $this->patch(route('payment-sheets.status', $batch), ['status' => 'reviewed'])
            ->assertRedirect()->assertSessionHas('success');
        $this->patch(route('payment-sheets.status', $batch), ['status' => 'approved'])
            ->assertRedirect()->assertSessionHas('success');
        $this->patch(route('payment-sheets.pay', $batch))->assertRedirect()->assertSessionHas('success');
        $batch->refresh();
        $this->assertSame('paid', $batch->status);
        $this->assertNotNull($batch->finance_transaction_id);
        $this->assertDatabaseHas('finance_transactions', [
            'id' => $batch->finance_transaction_id,
            'type' => 'pago',
            'status' => 'paid',
            'reference' => $batch->code,
            'amount' => 222,
        ]);

        $this->from(route('payment-sheets.index'))->patch(route('payment-sheets.pay', $batch))
            ->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors(['batch']);
        $this->assertDatabaseCount('finance_transactions', 1);
    }

    public function test_payment_sheet_requires_ordered_review_and_approval_with_audit_trail(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $this->collectMilk($producer, 10, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();
        $this->post(route('payment-sheets.close'), ['week_end' => $weekEnd])->assertRedirect();
        $batch = ProducerPaymentBatch::query()->firstOrFail();

        $this->from(route('payment-sheets.index'))->patch(route('payment-sheets.status', $batch), ['status' => 'approved'])
            ->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors(['batch']);

        $this->patch(route('payment-sheets.status', $batch), ['status' => 'reviewed'])->assertRedirect();
        $batch->refresh();
        $this->assertSame('reviewed', $batch->status);
        $this->assertSame($this->user->id, $batch->reviewed_by);
        $this->assertNotNull($batch->reviewed_at);

        $this->from(route('payment-sheets.index'))->patch(route('payment-sheets.price.revise', $batch), [
            'price_per_liter' => 21,
            'reason' => 'Intento posterior a la revisión',
        ])->assertRedirect(route('payment-sheets.index'))->assertSessionHasErrors(['batch']);

        $this->patch(route('payment-sheets.status', $batch), ['status' => 'approved'])->assertRedirect();
        $batch->refresh();
        $this->assertSame('approved', $batch->status);
        $this->assertSame($this->user->id, $batch->approved_by);
        $this->assertNotNull($batch->approved_at);
        $this->assertDatabaseHas('audit_events', [
            'entity_type' => ProducerPaymentBatch::class,
            'entity_id' => $batch->id,
            'action' => 'reviewed',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'entity_type' => ProducerPaymentBatch::class,
            'entity_id' => $batch->id,
            'action' => 'approved',
        ]);
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

    public function test_store_producer_rejects_scheme_relative_return_path(): void
    {
        $route = $this->createRoute();

        $this->post('/producers', [
            'full_name' => 'Cliente seguro',
            'identity_number' => '441-22222-0099B',
            'route_id' => $route->id,
            'return_to' => '//evil.example/phishing',
        ])->assertRedirect(route('producers.index'));
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

    public function test_week_adjustment_applies_density_price_and_advance_to_payroll(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'Con Multa']);
        $this->collectMilk($producer, 10, now()->toDateString(), null, 23.5);

        $weekEnd = app(ProducerService::class)
            ->currentPayThursday()
            ->toDateString();

        $this->put('/producers/'.$producer->id.'/week-adjustment', [
            'week_end' => $weekEnd,
            'density_price' => 15,
            'advance_amount' => 40,
            'notes' => 'Multa por densidad baja',
            'return_to' => '/producers?week='.$weekEnd,
        ])
            ->assertRedirect('/producers?week='.$weekEnd)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('producer_week_adjustments', [
            'producer_id' => $producer->id,
            'density_price' => 15,
            'advance_amount' => 40,
        ]);

        $this->assertTrue(
            ProducerWeekAdjustment::query()
                ->where('producer_id', $producer->id)
                ->whereDate('week_end', $weekEnd)
                ->exists()
        );

        // 10 L * 15 = 150 bruto − 40 adelanto = 110
        $this->get('/producers?week='.$weekEnd)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.rows.0.full_name', 'Con Multa')
                ->where('report.rows.0.density_price', 15)
                ->where('report.rows.0.advance_amount', 40)
                ->where('report.rows.0.gross_amount', 150)
                ->where('report.rows.0.amount', 110)
                ->where('report.totals.amount', 110)
                ->where('report.totals.penalized', 1)
                ->where('stats.penalized', 1)
            );

        $this->put('/producers/'.$producer->id.'/week-adjustment', [
            'week_end' => $weekEnd,
            'density_price' => 12,
            'advance_amount' => 20,
            'notes' => 'Actualización de penalización',
            'return_to' => '/producers?week='.$weekEnd,
        ])
            ->assertRedirect('/producers?week='.$weekEnd)
            ->assertSessionHas('success');

        $this->assertSame(
            1,
            ProducerWeekAdjustment::query()
                ->where('producer_id', $producer->id)
                ->whereDate('week_end', $weekEnd)
                ->count()
        );

        $this->get('/producers?week='.$weekEnd)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.rows.0.density_price', 12)
                ->where('report.rows.0.advance_amount', 20)
                ->where('report.rows.0.gross_amount', 120)
                ->where('report.rows.0.amount', 100)
            );
    }

    public function test_week_adjustment_can_set_a_permanent_custom_producer_price(): void
    {
        $this->createPrice(20);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'Precio Especial']);
        $this->collectMilk($producer, 10, now()->toDateString());
        $weekEnd = app(ProducerService::class)->currentPayThursday()->toDateString();

        $this->put('/producers/'.$producer->id.'/week-adjustment', [
            'week_end' => $weekEnd,
            'preferred_price_per_liter' => 23.50,
            'density_price' => null,
            'advance_amount' => 0,
            'notes' => 'Precio especial autorizado',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('producers', [
            'id' => $producer->id,
            'preferred_price_per_liter' => 23.50,
        ]);

        $this->get('/producers?week='.$weekEnd)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.rows.0.preferred_price', 23.5)
                ->where('report.rows.0.density_price', null)
                ->where('report.rows.0.price', 23.5)
                ->where('report.rows.0.gross_amount', 235)
                ->where('report.rows.0.amount', 235)
            );
    }
}
