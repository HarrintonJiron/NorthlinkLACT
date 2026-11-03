<?php

namespace App\Modules\Production\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Production\Models\ProductionBatch;
use App\Modules\Production\Models\ProductionWeightEntry;
use App\Modules\Production\Requests\CorrectProductionWeightRequest;
use App\Modules\Production\Requests\StoreBatchMilkInputRequest;
use App\Modules\Production\Requests\StoreFinishedProductLotRequest;
use App\Modules\Production\Requests\StoreProductionBatchRequest;
use App\Modules\Production\Requests\StoreProductionCostRequest;
use App\Modules\Production\Requests\StoreProductionWeightRequest;
use App\Modules\Production\Requests\UpdateProductionBatchStatusRequest;
use App\Modules\Production\Services\ProductionOutputService;
use App\Modules\Production\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductionBatchController extends Controller
{
    public function __construct(private ProductionService $productionService, private ProductionOutputService $outputService) {}

    public function index(): Response
    {
        $batches = ProductionBatch::query()
            ->with([
                'plant:id,name,code,company_id',
                'milkInputs:id,production_batch_id,source_date,liters',
                'weightEntries:id,production_batch_id,sequence,weight_lb,scale_reference,weighed_at,status,correction_reason,original_entry_id',
                'costs:id,production_batch_id,category,description,amount,created_at',
                'finishedLots.product:id,code,name,unit_id',
                'finishedLots.product.unit:id,code,name,symbol',
            ])
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (ProductionBatch $batch): array => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'status' => $batch->status,
                'plant' => $batch->plant,
                'started_at' => $batch->started_at?->toIso8601String(),
                'completed_at' => $batch->completed_at?->toIso8601String(),
                'cream_weight_lb' => (float) $batch->cream_weight_lb,
                'trimmings_weight_lb' => (float) $batch->trimmings_weight_lb,
                'reprocess_weight_lb' => (float) $batch->reprocess_weight_lb,
                'waste_weight_lb' => (float) $batch->waste_weight_lb,
                'notes' => $batch->notes,
                'milk_inputs' => $batch->milkInputs,
                'weight_entries' => $batch->weightEntries,
                'metrics' => $this->productionService->metrics($batch),
                'costs' => $batch->costs,
                'finished_lots' => $batch->finishedLots,
                'total_cost' => round((float) $batch->costs->sum('amount'), 2),
            ]);

        return Inertia::render('Production/Index', [
            'batches' => $batches,
            'stats' => [
                'total' => ProductionBatch::query()->count(),
                'open' => ProductionBatch::query()->whereIn('status', [
                    ProductionBatch::STATUS_PLANNED,
                    ProductionBatch::STATUS_IN_PROCESS,
                    ProductionBatch::STATUS_WEIGHING,
                ])->count(),
                'closed' => ProductionBatch::query()->where('status', ProductionBatch::STATUS_CLOSED)->count(),
                'released' => ProductionBatch::query()->where('status', ProductionBatch::STATUS_RELEASED)->count(),
            ],
            'inventoryProducts' => InventoryProduct::query()->where('active', true)->whereHas('unit', fn ($query) => $query->where('code', 'LB'))->with('unit:id,code,name,symbol')->orderBy('name')->get(['id', 'code', 'name', 'unit_id']),
        ]);
    }

    public function store(StoreProductionBatchRequest $request): RedirectResponse
    {
        $this->productionService->create($request->validated(), $request->user());

        return back()->with('success', 'Lote de producción creado correctamente.');
    }

    public function storeMilkInput(StoreBatchMilkInputRequest $request, ProductionBatch $batch): RedirectResponse
    {
        $this->productionService->addMilkInput($batch, $request->validated(), $request->user());

        return back()->with('success', 'Entrada de leche guardada.');
    }

    public function storeWeight(StoreProductionWeightRequest $request, ProductionBatch $batch): RedirectResponse
    {
        $this->productionService->addWeight($batch, $request->validated(), $request->user());

        return back()->with('success', 'Pesaje registrado.');
    }

    public function correctWeight(CorrectProductionWeightRequest $request, ProductionBatch $batch, ProductionWeightEntry $entry): RedirectResponse
    {
        $this->productionService->correctWeight($batch, $entry, $request->validated(), $request->user());

        return back()->with('success', 'Pesaje corregido con trazabilidad.');
    }

    public function updateStatus(UpdateProductionBatchStatusRequest $request, ProductionBatch $batch): RedirectResponse
    {
        $this->productionService->transition($batch, $request->validated(), $request->user());

        return back()->with('success', 'Estado del lote actualizado.');
    }

    public function storeCost(StoreProductionCostRequest $request, ProductionBatch $batch): RedirectResponse
    {
        $this->outputService->addCost($batch, $request->validated(), $request->user());

        return back()->with('success', 'Costo agregado al lote.');
    }

    public function storeFinishedLot(StoreFinishedProductLotRequest $request, ProductionBatch $batch): RedirectResponse
    {
        $this->outputService->createFinishedLot($batch, $request->validated(), $request->user());

        return back()->with('success', 'Producto terminado ingresado al inventario.');
    }
}
