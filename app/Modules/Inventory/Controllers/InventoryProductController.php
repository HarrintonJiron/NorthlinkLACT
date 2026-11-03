<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use App\Modules\Inventory\Requests\StoreInventoryProductRequest;
use App\Modules\Inventory\Requests\StoreInventoryProductsBulkRequest;
use App\Modules\Inventory\Requests\UpdateInventoryProductRequest;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryProductController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function index(Request $request)
    {
        $this->inventoryService->ensureDefaultUnits();
        $products = InventoryProduct::query()
            ->with('unit:id,code,name,symbol')
            ->when($request->string('search')->trim()->toString(), fn ($query, $search) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(30)->withQueryString();

        return Inertia::render('Inventory/Index', [
            'products' => $products->items(),
            'pagination' => ['links' => $products->linkCollection(), 'from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()],
            'movements' => InventoryMovement::query()
                ->with([
                    'product:id,code,name,unit_id',
                    'product.unit:id,code,name,symbol',
                    'user:id,name,username',
                ])
                ->latest()
                ->limit(30)
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
        $this->inventoryService->updateProduct($product, $request->safe()->except(['code']));

        return redirect()->route('inventory.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    public function toggle(InventoryProduct $product)
    {
        $product = $this->inventoryService->toggleProduct($product);
        $status = $product->active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Producto {$status} exitosamente.");
    }

    public function destroy(InventoryProduct $product)
    {
        $this->inventoryService->deleteProduct($product);

        return redirect()->route('inventory.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }
}
