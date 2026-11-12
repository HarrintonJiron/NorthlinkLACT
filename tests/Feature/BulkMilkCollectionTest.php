<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\ProducerPaymentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class BulkMilkCollectionTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_bulk_entry_lists_route_producers_and_existing_values(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'Ana Productora']);
        $collection = $this->collectMilk($producer, 18.5, now()->toDateString(), $route);

        $this->get(route('collections.bulk.index', ['route_id' => $route->id, 'week_start' => now()->startOfWeek()->toDateString()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Producers/Collections/BulkEntry')
                ->where('selectedRoute.id', $route->id)
                ->has('days', 7)
                ->has('rows', 1)
                ->where('rows.0.full_name', 'Ana Productora')
                ->where('rows.0.cells.'.now()->toDateString().'.liters', $collection->liters)
                ->where('rows.0.cells.'.now()->toDateString().'.exists', true));
    }

    public function test_bulk_entry_creates_multiple_manual_collections_atomically(): void
    {
        $route = $this->createRoute();
        $first = $this->createProducer($route);
        $second = $this->createProducer($route);
        $weekStart = now()->startOfWeek();

        $this->post(route('collections.bulk.store'), [
            'route_id' => $route->id,
            'week_start' => $weekStart->toDateString(),
            'entries' => [
                ['producer_id' => $first->id, 'date' => now()->toDateString(), 'liters' => '12.50'],
                ['producer_id' => $second->id, 'date' => now()->toDateString(), 'liters' => '20'],
            ],
        ])->assertRedirect(route('collections.bulk.index', ['route_id' => $route->id, 'week_start' => $weekStart->toDateString()]))
            ->assertSessionHas('success');

        $this->assertSame(2, MilkCollection::query()->count());
        $this->assertDatabaseHas('milk_collections', [
            'producer_id' => $first->id,
            'liters' => 12.50,
            'route_run_id' => null,
            'collected_by' => $this->user->id,
        ]);
    }

    public function test_bulk_entry_rejects_the_entire_batch_when_a_cell_already_exists(): void
    {
        $route = $this->createRoute();
        $first = $this->createProducer($route);
        $second = $this->createProducer($route);
        $this->collectMilk($first, 10, now()->toDateString(), $route);

        $this->from(route('collections.bulk.index'))->post(route('collections.bulk.store'), [
            'route_id' => $route->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'entries' => [
                ['producer_id' => $first->id, 'date' => now()->toDateString(), 'liters' => '15'],
                ['producer_id' => $second->id, 'date' => now()->toDateString(), 'liters' => '20'],
            ],
        ])->assertRedirect(route('collections.bulk.index'))->assertSessionHasErrors('entries.0');

        $this->assertSame(1, MilkCollection::query()->count());
        $this->assertFalse(MilkCollection::query()->where('producer_id', $second->id)->exists());
    }

    public function test_bulk_entry_rejects_cells_in_a_closed_payment_batch(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $weekStart = now()->startOfWeek();
        $batch = ProducerPaymentBatch::query()->create([
            'code' => 'PAY-TEST', 'week_start' => $weekStart, 'week_end' => $weekStart->copy()->endOfWeek(),
            'status' => ProducerPaymentBatch::STATUS_CLOSED, 'producer_count' => 1, 'total_liters' => 0,
            'gross_amount' => 0, 'advance_amount' => 0, 'net_amount' => 0,
            'closed_by' => $this->user->id, 'closed_at' => now(),
        ]);
        ProducerPaymentItem::query()->create([
            'batch_id' => $batch->id, 'producer_id' => $producer->id, 'route_id' => $route->id,
            'producer_code' => $producer->code, 'producer_name' => $producer->full_name, 'route_code' => $route->code,
            'liters' => 0, 'price_per_liter' => 0, 'gross_amount' => 0, 'advance_amount' => 0,
            'net_amount' => 0, 'daily_liters' => [],
        ]);

        $this->post(route('collections.bulk.store'), [
            'route_id' => $route->id,
            'week_start' => $weekStart->toDateString(),
            'entries' => [['producer_id' => $producer->id, 'date' => now()->toDateString(), 'liters' => '15']],
        ])->assertSessionHasErrors('entries.0.date');

        $this->assertSame(0, MilkCollection::query()->count());
    }
}
