<?php

namespace Tests\Feature;

use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_index_seeds_units_and_lists_products(): void
    {
        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Index')
                ->has('units')
                ->where('stats.total', 0)
                ->where('products', [])
            );

        $this->assertDatabaseHas('inventory_units', ['code' => 'L', 'name' => 'Litro']);
        $this->assertGreaterThanOrEqual(5, InventoryUnit::query()->count());
    }

    public function test_store_product_requires_name_and_unit(): void
    {
        $this->get('/inventory')->assertOk();

        $this->from('/inventory')->post('/inventory/products', [
            'name' => '',
            'unit_id' => null,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['name', 'unit_id']);
    }

    public function test_store_product_with_unit_and_auto_code(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'L')->firstOrFail();

        $this->post('/inventory/products', [
            'name' => 'Leche cruda',
            'description' => 'Acopio diario',
            'unit_id' => $unit->id,
            'stock' => 120.5,
            'min_stock' => 50,
            'code' => 'MANUAL-999',
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'name' => 'Leche cruda',
            'unit_id' => $unit->id,
            'code' => 'PRD-0001',
        ]);
        $this->assertDatabaseMissing('inventory_products', [
            'code' => 'MANUAL-999',
        ]);

        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products', 1)
                ->where('products.0.name', 'Leche cruda')
                ->where('products.0.unit.code', 'L')
                ->where('stats.total', 1)
                ->where('stats.active', 1)
                ->has('stats.monthly')
                ->has('stats.by_unit')
            );
    }

    public function test_store_product_with_optional_expiration_date(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'KG')->firstOrFail();

        $this->post('/inventory/products', [
            'name' => 'Yogurt',
            'unit_id' => $unit->id,
            'stock' => 40,
            'min_stock' => 10,
            'expiration_date' => '2026-12-15',
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'name' => 'Yogurt',
            'code' => 'PRD-0001',
        ]);
        $this->assertTrue(
            InventoryProduct::query()->where('name', 'Yogurt')->whereDate('expiration_date', '2026-12-15')->exists()
        );

        $this->post('/inventory/products', [
            'name' => 'Sal',
            'unit_id' => $unit->id,
            'stock' => 100,
            'min_stock' => 20,
            'expiration_date' => '',
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertNull(
            InventoryProduct::query()->where('name', 'Sal')->value('expiration_date')
        );
    }

    public function test_update_product_changes_unit_and_stock(): void
    {
        $this->get('/inventory')->assertOk();
        $litro = InventoryUnit::query()->where('code', 'L')->firstOrFail();
        $kg = InventoryUnit::query()->where('code', 'KG')->firstOrFail();

        $product = InventoryProduct::query()->create([
            'code' => 'PRD-0001',
            'name' => 'Queso',
            'unit_id' => $litro->id,
            'stock' => 10,
            'min_stock' => 2,
            'active' => true,
        ]);

        $this->put('/inventory/products/'.$product->id, [
            'name' => 'Queso fresco',
            'unit_id' => $kg->id,
            'stock' => 25,
            'min_stock' => 5,
            'active' => true,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'id' => $product->id,
            'name' => 'Queso fresco',
            'unit_id' => $kg->id,
            'stock' => 25,
        ]);
    }

    public function test_toggle_deactivates_product(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'UND')->firstOrFail();

        $product = InventoryProduct::query()->create([
            'code' => 'PRD-0002',
            'name' => 'Envase',
            'unit_id' => $unit->id,
            'stock' => 100,
            'min_stock' => 20,
            'active' => true,
        ]);

        $this->patch('/inventory/products/'.$product->id.'/toggle')
            ->assertRedirect();

        $this->assertFalse($product->fresh()->active);
    }

    public function test_bulk_store_creates_products_with_sequential_auto_codes(): void
    {
        $this->get('/inventory')->assertOk();
        $litro = InventoryUnit::query()->where('code', 'L')->firstOrFail();
        $kg = InventoryUnit::query()->where('code', 'KG')->firstOrFail();

        InventoryProduct::query()->create([
            'code' => 'PRD-0001',
            'name' => 'Existente',
            'unit_id' => $litro->id,
            'stock' => 1,
            'min_stock' => 0,
            'active' => true,
        ]);

        $this->post('/inventory/products/bulk', [
            'products' => [
                [
                    'name' => 'Leche pasteurizada',
                    'unit_id' => $litro->id,
                    'stock' => 50,
                    'min_stock' => 10,
                ],
                [
                    'name' => 'Crema',
                    'unit_id' => $kg->id,
                    'stock' => 20,
                    'min_stock' => 5,
                    'description' => 'Producto terminado',
                ],
            ],
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'name' => 'Leche pasteurizada',
            'code' => 'PRD-0002',
        ]);
        $this->assertDatabaseHas('inventory_products', [
            'name' => 'Crema',
            'code' => 'PRD-0003',
        ]);

        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products', 3)
                ->where('stats.total', 3)
            );
    }

    public function test_bulk_store_rejects_empty_product_list(): void
    {
        $this->get('/inventory')->assertOk();

        $this->from('/inventory')->post('/inventory/products/bulk', [
            'products' => [],
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['products']);
    }

    public function test_store_rejects_negative_stock_and_invalid_unit(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'L')->firstOrFail();

        $this->from('/inventory')->post('/inventory/products', [
            'name' => 'Producto inválido',
            'unit_id' => $unit->id,
            'stock' => -1,
            'min_stock' => -5,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['stock', 'min_stock']);

        $this->from('/inventory')->post('/inventory/products', [
            'name' => 'Producto sin unidad',
            'unit_id' => 999999,
            'stock' => 1,
            'min_stock' => 0,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['unit_id']);
    }

    public function test_store_rejects_inactive_unit(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'ML')->firstOrFail();
        $unit->update(['active' => false]);

        $this->from('/inventory')->post('/inventory/products', [
            'name' => 'No debería crear',
            'unit_id' => $unit->id,
            'stock' => 1,
            'min_stock' => 0,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['unit_id']);

        $this->assertDatabaseMissing('inventory_products', [
            'name' => 'No debería crear',
        ]);
    }

    public function test_bulk_store_rejects_more_than_fifty_and_negative_stock(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'L')->firstOrFail();

        $products = [];
        for ($i = 1; $i <= 51; $i++) {
            $products[] = [
                'name' => "Producto {$i}",
                'unit_id' => $unit->id,
                'stock' => 1,
                'min_stock' => 0,
            ];
        }

        $this->from('/inventory')->post('/inventory/products/bulk', [
            'products' => $products,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['products']);

        $this->from('/inventory')->post('/inventory/products/bulk', [
            'products' => [
                [
                    'name' => 'Negativo',
                    'unit_id' => $unit->id,
                    'stock' => -10,
                    'min_stock' => 0,
                ],
            ],
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['products.0.stock']);
    }

    public function test_destroy_soft_deletes_and_next_code_continues(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'KG')->firstOrFail();

        $first = InventoryProduct::query()->create([
            'code' => 'PRD-0001',
            'name' => 'Temporal',
            'unit_id' => $unit->id,
            'stock' => 5,
            'min_stock' => 1,
            'active' => true,
        ]);

        $this->delete('/inventory/products/'.$first->id)
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertSoftDeleted('inventory_products', ['id' => $first->id]);

        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('products', [])
                ->where('stats.total', 0)
            );

        $this->post('/inventory/products', [
            'name' => 'Después de eliminar',
            'unit_id' => $unit->id,
            'stock' => 3,
            'min_stock' => 1,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'name' => 'Después de eliminar',
            'code' => 'PRD-0002',
            'deleted_at' => null,
        ]);
    }

    public function test_toggle_reactivates_product_and_stats_track_low_stock(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'L')->firstOrFail();

        $low = InventoryProduct::query()->create([
            'code' => 'PRD-0001',
            'name' => 'Stock bajo',
            'unit_id' => $unit->id,
            'stock' => 2,
            'min_stock' => 5,
            'active' => true,
        ]);

        $zero = InventoryProduct::query()->create([
            'code' => 'PRD-0002',
            'name' => 'Sin stock',
            'unit_id' => $unit->id,
            'stock' => 0,
            'min_stock' => 0,
            'active' => true,
        ]);

        $inactive = InventoryProduct::query()->create([
            'code' => 'PRD-0003',
            'name' => 'Inactivo con bajo stock',
            'unit_id' => $unit->id,
            'stock' => 0,
            'min_stock' => 10,
            'active' => false,
        ]);

        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 3)
                ->where('stats.active', 2)
                ->where('stats.inactive', 1)
                ->where('stats.low_stock', 2)
                ->where('stats.zero_stock', 1)
            );

        $this->patch('/inventory/products/'.$inactive->id.'/toggle')
            ->assertRedirect();

        $this->assertTrue($inactive->fresh()->active);

        $this->get('/inventory')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.active', 3)
                ->where('stats.inactive', 0)
                ->where('stats.low_stock', 3)
                ->where('stats.zero_stock', 2)
            );

        $this->assertTrue($low->fresh()->active);
        $this->assertTrue($zero->fresh()->active);
    }

    public function test_update_rejects_negative_stock_and_keeps_code_immutable(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'UND')->firstOrFail();

        $product = InventoryProduct::query()->create([
            'code' => 'PRD-0007',
            'name' => 'Original',
            'unit_id' => $unit->id,
            'stock' => 10,
            'min_stock' => 2,
            'active' => true,
        ]);

        $this->from('/inventory')->put('/inventory/products/'.$product->id, [
            'name' => 'Original',
            'unit_id' => $unit->id,
            'stock' => -3,
            'min_stock' => 2,
            'active' => true,
            'code' => 'HACK-999',
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['stock']);

        $this->put('/inventory/products/'.$product->id, [
            'name' => 'Actualizado',
            'unit_id' => $unit->id,
            'stock' => 15,
            'min_stock' => 2,
            'active' => true,
            'code' => 'HACK-999',
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'id' => $product->id,
            'name' => 'Actualizado',
            'code' => 'PRD-0007',
            'stock' => 15,
        ]);
        $this->assertDatabaseMissing('inventory_products', [
            'code' => 'HACK-999',
        ]);
    }

    public function test_update_allows_existing_inactive_unit(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'ML')->firstOrFail();

        $product = InventoryProduct::query()->create([
            'code' => 'PRD-0010',
            'name' => 'Producto legacy',
            'unit_id' => $unit->id,
            'stock' => 5,
            'min_stock' => 1,
            'active' => true,
        ]);

        $unit->update(['active' => false]);

        $this->put('/inventory/products/'.$product->id, [
            'name' => 'Producto legacy actualizado',
            'unit_id' => $unit->id,
            'stock' => 8,
            'min_stock' => 2,
            'active' => true,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_products', [
            'id' => $product->id,
            'name' => 'Producto legacy actualizado',
            'unit_id' => $unit->id,
            'stock' => 8,
        ]);
    }

    public function test_update_rejects_switching_to_inactive_unit(): void
    {
        $this->get('/inventory')->assertOk();
        $activeUnit = InventoryUnit::query()->where('code', 'L')->firstOrFail();
        $inactiveUnit = InventoryUnit::query()->where('code', 'GAL')->firstOrFail();
        $inactiveUnit->update(['active' => false]);

        $product = InventoryProduct::query()->create([
            'code' => 'PRD-0011',
            'name' => 'Con unidad activa',
            'unit_id' => $activeUnit->id,
            'stock' => 5,
            'min_stock' => 1,
            'active' => true,
        ]);

        $this->from('/inventory')->put('/inventory/products/'.$product->id, [
            'name' => 'Con unidad activa',
            'unit_id' => $inactiveUnit->id,
            'stock' => 5,
            'min_stock' => 1,
            'active' => true,
        ])
            ->assertRedirect('/inventory')
            ->assertSessionHasErrors(['unit_id']);
    }

    public function test_mixed_single_and_bulk_store_keep_sequential_codes(): void
    {
        $this->get('/inventory')->assertOk();
        $unit = InventoryUnit::query()->where('code', 'L')->firstOrFail();

        $this->post('/inventory/products', [
            'name' => 'Individual',
            'unit_id' => $unit->id,
            'stock' => 1,
            'min_stock' => 0,
        ])->assertRedirect('/inventory');

        $this->post('/inventory/products/bulk', [
            'products' => [
                ['name' => 'Bulk A', 'unit_id' => $unit->id, 'stock' => 2, 'min_stock' => 0],
                ['name' => 'Bulk B', 'unit_id' => $unit->id, 'stock' => 3, 'min_stock' => 0],
            ],
        ])->assertRedirect('/inventory');

        $this->assertDatabaseHas('inventory_products', ['name' => 'Individual', 'code' => 'PRD-0001']);
        $this->assertDatabaseHas('inventory_products', ['name' => 'Bulk A', 'code' => 'PRD-0002']);
        $this->assertDatabaseHas('inventory_products', ['name' => 'Bulk B', 'code' => 'PRD-0003']);
    }
}
