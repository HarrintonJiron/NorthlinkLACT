<?php

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Production\Models\FinishedProductLot;
use App\Modules\Production\Models\ProductDispatch;
use App\Modules\Production\Models\ProductionBatch;
use App\Modules\Production\Models\ProductionCostEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionOutputService
{
    public function __construct(private InventoryService $inventory, private ProductionService $production) {}

    public function addCost(ProductionBatch $batch, array $data, User $user): ProductionCostEntry
    {
        if ($batch->status === ProductionBatch::STATUS_RELEASED) {
            throw ValidationException::withMessages(['batch' => 'No se agregan costos después de liberar el lote.']);
        }

        return $batch->costs()->create([...$data, 'recorded_by' => $user->id]);
    }

    public function createFinishedLot(ProductionBatch $batch, array $data, User $user): FinishedProductLot
    {
        if ($batch->status !== ProductionBatch::STATUS_RELEASED) {
            throw ValidationException::withMessages(['batch' => 'El lote de producción debe estar liberado.']);
        }

        return DB::transaction(function () use ($batch, $data, $user) {
            ProductionBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $product = InventoryProduct::query()->with('unit')->lockForUpdate()->findOrFail($data['inventory_product_id']);
            if ($product->unit?->code !== 'LB') {
                throw ValidationException::withMessages(['inventory_product_id' => 'El producto terminado debe usar la unidad libra (lb), igual que los pesajes de producción.']);
            }
            $totalCost = (float) $batch->costs()->sum('amount');
            $quantity = (float) $data['quantity'];
            $producedPounds = (float) $this->production->metrics($batch)['total_output_lb'];
            if ($producedPounds <= 0) {
                throw ValidationException::withMessages(['quantity' => 'El lote no tiene producción pesada para calcular el costo unitario.']);
            }
            $alreadyRegistered = (float) $batch->finishedLots()->sum('quantity');
            if (round($alreadyRegistered + $quantity, 3) > round($producedPounds, 3)) {
                throw ValidationException::withMessages(['quantity' => 'La cantidad terminada supera las libras producidas por el lote.']);
            }
            $sequence = $batch->finishedLots()->count() + 1;
            $lot = $batch->finishedLots()->create([
                'inventory_product_id' => $product->id, 'lot_number' => $batch->batch_number.'-PT-'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
                'quantity' => $quantity, 'available_quantity' => $quantity,
                'unit_cost' => round($totalCost / $producedPounds, 4),
                'expiration_date' => $data['expiration_date'] ?? null, 'status' => 'available', 'created_by' => $user->id,
            ]);
            $this->inventory->recordMovement($product, 'production', $quantity, "Entrada de producto terminado {$lot->lot_number}", $lot->lot_number, $lot);
            $this->audit($lot, $user, 'created', 'Lote de producto terminado registrado');

            return $lot;
        });
    }

    public function dispatch(array $data, User $user): ProductDispatch
    {
        return DB::transaction(function () use ($data, $user) {
            $dispatch = ProductDispatch::query()->create([
                'code' => 'REM-'.now()->format('Ymd-His').'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
                'dispatch_date' => $data['dispatch_date'], 'destination' => $data['destination'],
                'recipient' => $data['recipient'] ?? null, 'notes' => $data['notes'] ?? null,
                'status' => 'dispatched', 'created_by' => $user->id, 'dispatched_at' => now(),
            ]);
            foreach ($data['items'] as $row) {
                $lot = FinishedProductLot::query()->lockForUpdate()->findOrFail($row['lot_id']);
                $quantity = round((float) $row['quantity'], 3);
                if ($quantity > (float) $lot->available_quantity) {
                    throw ValidationException::withMessages(['items' => "El lote {$lot->lot_number} no tiene cantidad suficiente."]);
                }
                $product = InventoryProduct::query()->lockForUpdate()->findOrFail($lot->inventory_product_id);
                $item = $dispatch->items()->create(['finished_product_lot_id' => $lot->id, 'inventory_product_id' => $product->id, 'quantity' => $quantity, 'unit_cost' => $lot->unit_cost]);
                $remaining = round((float) $lot->available_quantity - $quantity, 3);
                $lot->update(['available_quantity' => $remaining, 'status' => $remaining <= 0 ? 'depleted' : 'available']);
                $this->inventory->recordMovement($product, 'dispatch', -$quantity, "Salida por remisión {$dispatch->code}", $dispatch->code, $item);
            }
            $this->audit($dispatch, $user, 'dispatched', 'Remisión de producto terminada despachada');

            return $dispatch->load('items');
        });
    }

    public function receive(ProductDispatch $dispatch, User $user): ProductDispatch
    {
        return DB::transaction(function () use ($dispatch, $user) {
            $locked = ProductDispatch::query()->lockForUpdate()->findOrFail($dispatch->id);
            if ($locked->status !== 'dispatched') {
                throw ValidationException::withMessages(['dispatch' => 'La remisión ya fue recibida.']);
            }
            $locked->update(['status' => 'received', 'received_by' => $user->id, 'received_at' => now()]);
            $this->audit($locked, $user, 'received', 'Recepción de remisión confirmada');

            return $locked->refresh();
        });
    }

    private function audit($entity, User $user, string $action, string $description): void
    {
        AuditEvent::query()->create(['user_id' => $user->id, 'entity_type' => $entity::class, 'entity_id' => $entity->id, 'action' => $action, 'description' => $description, 'new_values' => $entity->fresh()->toArray(), 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
    }
}
