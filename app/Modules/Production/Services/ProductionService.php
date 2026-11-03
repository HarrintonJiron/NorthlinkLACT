<?php

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Admin\Models\Plant;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Production\Models\BatchMilkInput;
use App\Modules\Production\Models\ProductionBatch;
use App\Modules\Production\Models\ProductionWeightEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    public function create(array $data, User $user): ProductionBatch
    {
        return DB::transaction(function () use ($data, $user): ProductionBatch {
            $plant = Plant::query()->where('active', true)->orderBy('id')->firstOrFail();
            $batch = ProductionBatch::query()->create([
                'plant_id' => $plant->id,
                'batch_number' => $this->nextBatchNumber(),
                'status' => ProductionBatch::STATUS_PLANNED,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->addMilkInput($batch, $data, $user);
            $this->audit($batch, $user, 'create', 'Creación de lote de producción');

            return $batch->fresh();
        });
    }

    public function addMilkInput(ProductionBatch $batch, array $data, User $user): BatchMilkInput
    {
        $this->ensureEditable($batch);

        return BatchMilkInput::query()->updateOrCreate(
            ['production_batch_id' => $batch->id, 'source_date' => $data['source_date']],
            ['liters' => $data['liters'], 'recorded_by' => $user->id],
        );
    }

    public function addWeight(ProductionBatch $batch, array $data, User $user): ProductionWeightEntry
    {
        $this->ensureProcessing($batch);

        return DB::transaction(function () use ($batch, $data, $user): ProductionWeightEntry {
            ProductionBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $sequence = (int) $batch->weightEntries()->max('sequence') + 1;
            $entry = $batch->weightEntries()->create([
                'sequence' => $sequence,
                'weight_lb' => $data['weight_lb'],
                'scale_reference' => $data['scale_reference'] ?? null,
                'weighed_at' => $data['weighed_at'],
                'weighed_by' => $user->id,
                'status' => ProductionWeightEntry::STATUS_ACTIVE,
            ]);

            if ($batch->status === ProductionBatch::STATUS_IN_PROCESS) {
                $batch->update(['status' => ProductionBatch::STATUS_WEIGHING]);
            }

            $this->audit($batch, $user, 'weight', "Pesaje #{$sequence} registrado");

            return $entry;
        });
    }

    public function correctWeight(ProductionBatch $batch, ProductionWeightEntry $entry, array $data, User $user): ProductionWeightEntry
    {
        $this->ensureProcessing($batch);
        abort_unless($entry->production_batch_id === $batch->id, 404);

        if ($entry->status !== ProductionWeightEntry::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['weight_lb' => 'Este pesaje ya fue corregido.']);
        }

        return DB::transaction(function () use ($batch, $entry, $data, $user): ProductionWeightEntry {
            ProductionBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $entry->update([
                'status' => ProductionWeightEntry::STATUS_CORRECTED,
                'correction_reason' => $data['reason'],
            ]);

            $sequence = (int) $batch->weightEntries()->max('sequence') + 1;
            $replacement = $batch->weightEntries()->create([
                'sequence' => $sequence,
                'weight_lb' => $data['weight_lb'],
                'scale_reference' => $entry->scale_reference,
                'weighed_at' => now(),
                'weighed_by' => $user->id,
                'original_entry_id' => $entry->id,
                'status' => ProductionWeightEntry::STATUS_ACTIVE,
                'correction_reason' => $data['reason'],
            ]);

            $this->audit($batch, $user, 'correct_weight', "Pesaje #{$entry->sequence} corregido por #{$sequence}");

            return $replacement;
        });
    }

    public function transition(ProductionBatch $batch, array $data, User $user): ProductionBatch
    {
        $target = $data['status'];
        $allowed = [
            ProductionBatch::STATUS_PLANNED => [ProductionBatch::STATUS_IN_PROCESS],
            ProductionBatch::STATUS_IN_PROCESS => [ProductionBatch::STATUS_CLOSED],
            ProductionBatch::STATUS_WEIGHING => [ProductionBatch::STATUS_CLOSED],
            ProductionBatch::STATUS_CLOSED => [ProductionBatch::STATUS_RELEASED],
            ProductionBatch::STATUS_RELEASED => [],
        ];

        if (! in_array($target, $allowed[$batch->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'La transición solicitada no es válida para este lote.']);
        }

        if ($target === ProductionBatch::STATUS_CLOSED && ! $batch->activeWeightEntries()->exists()) {
            throw ValidationException::withMessages(['status' => 'Registra al menos un pesaje antes de cerrar el lote.']);
        }

        $payload = ['status' => $target];
        if ($target === ProductionBatch::STATUS_IN_PROCESS) {
            $payload['started_at'] = now();
        }
        if ($target === ProductionBatch::STATUS_CLOSED) {
            $payload = [...$payload,
                'completed_at' => now(),
                'closed_by' => $user->id,
                'cream_weight_lb' => $data['cream_weight_lb'] ?? 0,
                'trimmings_weight_lb' => $data['trimmings_weight_lb'] ?? 0,
                'reprocess_weight_lb' => $data['reprocess_weight_lb'] ?? 0,
                'waste_weight_lb' => $data['waste_weight_lb'] ?? 0,
            ];
        }

        $old = $batch->getAttributes();
        $batch->update($payload);
        $this->audit($batch, $user, 'status', "Lote actualizado a {$target}", $old);

        return $batch->fresh();
    }

    public function metrics(ProductionBatch $batch): array
    {
        $liters = round((float) $batch->milkInputs->sum('liters'), 3);
        $gallons = round($liters / 4, 3);
        $cheese = round((float) $batch->activeWeightEntries->sum('weight_lb'), 3);
        $total = round($cheese + (float) $batch->cream_weight_lb + (float) $batch->trimmings_weight_lb, 3);

        return [
            'input_liters' => $liters,
            'equivalent_gallons' => $gallons,
            'cheese_weight_lb' => $cheese,
            'total_output_lb' => $total,
            'cheese_yield_lb_per_gallon' => $gallons > 0 ? round($cheese / $gallons, 4) : 0,
            'total_yield_lb_per_gallon' => $gallons > 0 ? round($total / $gallons, 4) : 0,
        ];
    }

    private function nextBatchNumber(): string
    {
        $prefix = 'LOT-'.now()->format('ymd').'-';
        $last = ProductionBatch::query()->where('batch_number', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($last + 1), 3, '0', STR_PAD_LEFT);
    }

    private function ensureEditable(ProductionBatch $batch): void
    {
        if (in_array($batch->status, [ProductionBatch::STATUS_CLOSED, ProductionBatch::STATUS_RELEASED], true)) {
            throw ValidationException::withMessages(['batch' => 'Un lote cerrado o liberado no puede modificarse.']);
        }
    }

    private function ensureProcessing(ProductionBatch $batch): void
    {
        if (! in_array($batch->status, [ProductionBatch::STATUS_IN_PROCESS, ProductionBatch::STATUS_WEIGHING], true)) {
            throw ValidationException::withMessages(['batch' => 'El lote debe estar en proceso para registrar o corregir pesajes.']);
        }
    }

    private function audit(ProductionBatch $batch, User $user, string $action, string $description, ?array $old = null): void
    {
        AuditEvent::query()->create([
            'user_id' => $user->id,
            'company_id' => $batch->plant->company_id,
            'plant_id' => $batch->plant_id,
            'entity_type' => ProductionBatch::class,
            'entity_id' => $batch->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $batch->fresh()->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
