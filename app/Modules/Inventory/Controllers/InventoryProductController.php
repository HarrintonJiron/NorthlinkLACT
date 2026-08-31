<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use App\Modules\Inventory\Requests\StoreInventoryProductRequest;
use App\Modules\Inventory\Requests\StoreInventoryProductsBulkRequest;
use App\Modules\Inventory\Requests\UpdateInventoryProductRequest;
use App\Modules\Inventory\Services\InventoryService;
use Inertia\Inertia;

class InventoryProductController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function index()
    {
        $this->inventoryService->ensureDefaultUnits();

        return Inertia::render('Inventory/Index', [
            'products' => InventoryProduct::query()
                ->with('unit:id,code,name,symbol')
                ->orderBy('name')
                ->get(),
            'units' => InventoryUnit::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'symbol']),
            'stats' => $this->inventoryService->stats(),
        ]);
    }

    public function store(StoreInventoryProductRequest $request)
    {
        $data = $request->safe()->except(['code']);

        $this->inventoryService->createProduct($data);

        return redirect()->route('inventory.index')
            ->with('success', 'Producto registrado exitosamente.');
    }

    public function storeBulk(StoreInventoryProductsBulkRequest $request)
    {
        $items = collect($request->validated('products'))
            ->map(fn (array $item) => [
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'unit_id' => (int) $item['unit_id'],
                'stock' => (float) ($item['stock'] ?? 0),
                'min_stock' => (float) ($item['min_stock'] ?? 0),
            ])
            ->all();

        $created = $this->inventoryService->createProductsBulk($items);

        return redirect()->route('inventory.index')
            ->with('success', "{$created} producto".($created === 1 ? '' : 's').' registrado'.($created === 1 ? '' : 's').' exitosamente.');
    }

    public function update(UpdateInventoryProductRequest $request, InventoryProduct $product)
    {
        $data = $request->safe()->except(['code']);
        $data['stock'] = (float) ($data['stock'] ?? 0);
        $data['min_stock'] = (float) ($data['min_stock'] ?? 0);
        $data['expiration_date'] = $data['expiration_date'] ?? null;
        if (array_key_exists('active', $data)) {
            $data['active'] = (bool) $data['active'];
        }

        $product->update($data);

        return redirect()->route('inventory.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function toggle(InventoryProduct $product)
    {
        $product->update(['active' => ! $product->active]);
        $status = $product->active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Producto {$status} exitosamente.");
    }

    public function destroy(InventoryProduct $product)
    {
        $product->delete();

        return redirect()->route('inventory.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }
}
