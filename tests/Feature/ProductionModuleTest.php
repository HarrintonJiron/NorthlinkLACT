<?php

namespace Tests\Feature;

use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use App\Modules\Production\Models\FinishedProductLot;
use App\Modules\Production\Models\ProductDispatch;
use App\Modules\Production\Models\ProductionBatch;
use App\Modules\Production\Models\ProductionWeightEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticate();
        $company = Company::factory()->create();
        Plant::factory()->for($company)->create(['active' => true]);
    }

    public function test_batch_flow_calculates_confirmed_yields_and_releases_closed_batch(): void
    {
        $this->post(route('production.store'), [
            'source_date' => now()->toDateString(),
            'liters' => 26697,
            'notes' => 'Producción de prueba documental',
        ])->assertRedirect()->assertSessionHas('success');

        $batch = ProductionBatch::query()->firstOrFail();
        $this->assertSame(ProductionBatch::STATUS_PLANNED, $batch->status);

        $this->patch(route('production.status.update', $batch), ['status' => 'in_process'])
            ->assertRedirect();

        $this->post(route('production.weights.store', $batch), [
            'weight_lb' => 5500,
            'scale_reference' => 'Báscula principal',
            'weighed_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->patch(route('production.status.update', $batch), [
            'status' => 'closed',
            'cream_weight_lb' => 250,
            'trimmings_weight_lb' => 90,
            'reprocess_weight_lb' => 0,
            'waste_weight_lb' => 15,
        ])->assertRedirect();

        $this->get(route('production.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Production/Index')
                ->where('batches.0.metrics.input_liters', 26697)
                ->where('batches.0.metrics.equivalent_gallons', 6674.25)
                ->where('batches.0.metrics.cheese_weight_lb', 5500)
                ->where('batches.0.metrics.total_output_lb', 5840)
                ->where('batches.0.metrics.cheese_yield_lb_per_gallon', 0.8241)
                ->where('batches.0.metrics.total_yield_lb_per_gallon', 0.875));

        $this->patch(route('production.status.update', $batch), ['status' => 'released'])
            ->assertRedirect();
        $this->assertSame(ProductionBatch::STATUS_RELEASED, $batch->fresh()->status);
    }

    public function test_closed_batch_rejects_new_inputs_and_weights(): void
    {
        $batch = $this->createClosedBatch();

        $this->from(route('production.index'))->post(route('production.milk-inputs.store', $batch), [
            'source_date' => now()->toDateString(),
            'liters' => 10,
        ])->assertRedirect(route('production.index'))->assertSessionHasErrors('batch');

        $this->from(route('production.index'))->post(route('production.weights.store', $batch), [
            'weight_lb' => 10,
            'weighed_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('production.index'))->assertSessionHasErrors('batch');
    }

    public function test_weight_correction_preserves_original_entry(): void
    {
        $batch = $this->createBatchInProcess();
        $this->post(route('production.weights.store', $batch), [
            'weight_lb' => 100,
            'weighed_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertRedirect();
        $entry = ProductionWeightEntry::query()->firstOrFail();

        $this->post(route('production.weights.correct', [$batch, $entry]), [
            'weight_lb' => 98.5,
            'reason' => 'Lectura incorrecta de la báscula',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(ProductionWeightEntry::STATUS_CORRECTED, $entry->fresh()->status);
        $this->assertDatabaseHas('production_weight_entries', [
            'original_entry_id' => $entry->id,
            'weight_lb' => 98.5,
            'status' => ProductionWeightEntry::STATUS_ACTIVE,
        ]);
        $this->assertDatabaseCount('production_weight_entries', 2);
    }

    public function test_cannot_close_batch_without_a_weight(): void
    {
        $batch = $this->createBatchInProcess();

        $this->from(route('production.index'))
            ->patch(route('production.status.update', $batch), ['status' => 'closed'])
            ->assertRedirect(route('production.index'))
            ->assertSessionHasErrors('status');
    }

    public function test_finished_product_enters_kardex_with_batch_cost_and_dispatch_reduces_stock(): void
    {
        $batch = $this->createBatchInProcess();
        $this->post(route('production.costs.store', $batch), ['category' => 'milk', 'description' => 'Leche procesada', 'amount' => 800])->assertRedirect();
        $this->post(route('production.costs.store', $batch), ['category' => 'energy', 'description' => 'Energía', 'amount' => 200])->assertRedirect();
        $this->post(route('production.weights.store', $batch), ['weight_lb' => 100, 'weighed_at' => now()->subMinute()->format('Y-m-d H:i:s')])->assertRedirect();
        $this->patch(route('production.status.update', $batch), ['status' => 'closed'])->assertRedirect();
        $this->patch(route('production.status.update', $batch), ['status' => 'released'])->assertRedirect();

        $unit = InventoryUnit::query()->create(['code' => 'LB', 'name' => 'Libra', 'symbol' => 'lb', 'active' => true]);
        $product = InventoryProduct::query()->create(['code' => 'PRD-Q01', 'name' => 'Queso fresco', 'unit_id' => $unit->id, 'stock' => 0, 'min_stock' => 0, 'active' => true]);
        $this->post(route('production.finished-lots.store', $batch), ['inventory_product_id' => $product->id, 'quantity' => 100])->assertRedirect()->assertSessionHas('success');

        $lot = FinishedProductLot::query()->firstOrFail();
        $this->assertSame('10.0000', $lot->unit_cost);
        $this->assertSame('100.000', $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => 'production', 'new_stock' => 100]);

        $this->post(route('dispatches.store'), ['dispatch_date' => now()->toDateString(), 'destination' => 'Oficina Waslala', 'recipient' => 'Bodega', 'items' => [['lot_id' => $lot->id, 'quantity' => 30]]])->assertRedirect()->assertSessionHas('success');
        $dispatch = ProductDispatch::query()->firstOrFail();
        $this->assertSame('70.000', $lot->fresh()->available_quantity);
        $this->assertSame('70.000', $product->fresh()->stock);
        $this->assertSame(-1, InventoryMovement::query()->where('type', 'dispatch')->firstOrFail()->direction);

        $this->patch(route('dispatches.receive', $dispatch))->assertRedirect()->assertSessionHas('success');
        $this->assertSame('received', $dispatch->fresh()->status);
    }

    public function test_dispatch_cannot_exceed_lot_availability_and_rolls_back_everything(): void
    {
        $batch = $this->createClosedBatch();
        $this->patch(route('production.status.update', $batch), ['status' => 'released'])->assertRedirect();
        $unit = InventoryUnit::query()->create(['code' => 'LB', 'name' => 'Libra', 'symbol' => 'lb', 'active' => true]);
        $product = InventoryProduct::query()->create(['code' => 'PRD-Q02', 'name' => 'Queso seco', 'unit_id' => $unit->id, 'stock' => 0, 'min_stock' => 0, 'active' => true]);
        $this->post(route('production.finished-lots.store', $batch), ['inventory_product_id' => $product->id, 'quantity' => 50])->assertRedirect();
        $lot = FinishedProductLot::query()->firstOrFail();

        $this->from(route('dispatches.index'))->post(route('dispatches.store'), ['dispatch_date' => now()->toDateString(), 'destination' => 'Cliente', 'items' => [['lot_id' => $lot->id, 'quantity' => 51]]])
            ->assertRedirect(route('dispatches.index'))->assertSessionHasErrors(['items']);
        $this->assertDatabaseCount('product_dispatches', 0);
        $this->assertSame('50.000', $lot->fresh()->available_quantity);
        $this->assertSame('50.000', $product->fresh()->stock);
    }

    private function createBatchInProcess(): ProductionBatch
    {
        $this->post(route('production.store'), [
            'source_date' => now()->toDateString(),
            'liters' => 400,
        ])->assertRedirect();
        $batch = ProductionBatch::query()->latest('id')->firstOrFail();
        $this->patch(route('production.status.update', $batch), ['status' => 'in_process'])->assertRedirect();

        return $batch->fresh();
    }

    private function createClosedBatch(): ProductionBatch
    {
        $batch = $this->createBatchInProcess();
        $this->post(route('production.weights.store', $batch), [
            'weight_lb' => 80,
            'weighed_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertRedirect();
        $this->patch(route('production.status.update', $batch), ['status' => 'closed'])->assertRedirect();

        return $batch->fresh();
    }
}
