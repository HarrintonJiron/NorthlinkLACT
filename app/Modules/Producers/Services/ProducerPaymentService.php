<?php

namespace App\Modules\Producers\Services;

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Finanzas\Services\FinanzasService;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProducerPaymentService
{
    public function __construct(private ProducerService $producers, private FinanzasService $finances) {}

    public function close(string $weekEnd, User $user, ?string $notes = null): ProducerPaymentBatch
    {
        return DB::transaction(function () use ($weekEnd, $user, $notes) {
            // Serializa cierres semanales incluso cuando aún no existe una fila para la semana.
            DB::table('companies')->orderBy('id')->lockForUpdate()->first();
            $report = $this->producers->weeklyPayroll(null, $weekEnd);
            if ((float) $report['price'] <= 0 || ! count($report['rows'])) {
                throw ValidationException::withMessages(['week_end' => 'La semana necesita precio vigente y productores asignados antes de cerrarse.']);
            }

            if (ProducerPaymentBatch::query()->whereDate('week_end', $report['week']['end'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['week_end' => 'Esta semana ya tiene una planilla cerrada.']);
            }

            $batch = ProducerPaymentBatch::query()->create([
                'code' => 'PPR-'.$report['week']['end'], 'week_start' => $report['week']['start'],
                'week_end' => $report['week']['end'], 'status' => ProducerPaymentBatch::STATUS_CLOSED,
                'producer_count' => $report['totals']['producers'], 'total_liters' => $report['totals']['liters'],
                'gross_amount' => $report['totals']['gross_amount'], 'advance_amount' => $report['totals']['advance_amount'],
                'net_amount' => $report['totals']['amount'], 'closed_by' => $user->id, 'closed_at' => now(), 'notes' => $notes,
            ]);

            foreach ($report['rows'] as $row) {
                $batch->items()->create([
                    'producer_id' => $row['id'], 'route_id' => $row['route']['id'] ?? null,
                    'producer_code' => $row['code'], 'producer_name' => $row['full_name'],
                    'route_code' => $row['route']['code'] ?? null, 'liters' => $row['liters'],
                    'price_per_liter' => $row['price'], 'gross_amount' => $row['gross_amount'],
                    'advance_amount' => $row['advance_amount'], 'net_amount' => $row['amount'],
                    'daily_liters' => $row['daily'], 'average_density' => $row['avg_density'], 'notes' => $row['notes'],
                ]);
            }

            $this->audit($batch, $user, 'closed', 'Planilla semanal cerrada');

            return $batch->load('items');
        });
    }

    public function setWeeklyPrice(string $weekEnd, string $pricePerLiter, string $reason, User $user): MilkPrice
    {
        return DB::transaction(function () use ($weekEnd, $pricePerLiter, $reason, $user): MilkPrice {
            $range = $this->producers->weekRange($weekEnd);

            if (ProducerPaymentBatch::query()->whereDate('week_end', $range['end'])->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['week_end' => 'La planilla ya está cerrada y su precio no puede modificarse.']);
            }

            $currentPrice = MilkPrice::query()
                ->effectiveOn($range['end'])
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();
            $route = Route::query()->orderBy('id')->first();
            $companyId = $currentPrice?->company_id ?? $route?->company_id;
            $plantId = $currentPrice?->plant_id ?? $route?->plant_id;

            if (! $companyId) {
                throw ValidationException::withMessages(['price_per_liter' => 'Configura primero la empresa y la planta de acopio.']);
            }

            $weeklyPrice = MilkPrice::query()->firstOrNew([
                'company_id' => $companyId,
                'plant_id' => $plantId,
                'effective_from' => $range['start'],
                'effective_until' => $range['end'],
            ]);
            $oldValues = $weeklyPrice->exists ? $weeklyPrice->toArray() : null;
            $weeklyPrice->fill([
                'price_per_liter' => $pricePerLiter,
                'active' => true,
            ])->save();

            AuditEvent::query()->create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'plant_id' => $plantId,
                'entity_type' => MilkPrice::class,
                'entity_id' => $weeklyPrice->id,
                'action' => $oldValues ? 'updated' : 'created',
                'description' => "Precio de planilla {$range['start']} a {$range['end']}: {$reason}",
                'old_values' => $oldValues,
                'new_values' => $weeklyPrice->fresh()->toArray(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $weeklyPrice->refresh();
        });
    }

    public function reviseClosedPrice(ProducerPaymentBatch $batch, string $pricePerLiter, string $reason, User $user): ProducerPaymentBatch
    {
        return DB::transaction(function () use ($batch, $pricePerLiter, $reason, $user): ProducerPaymentBatch {
            $locked = ProducerPaymentBatch::query()->with('items')->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== ProducerPaymentBatch::STATUS_CLOSED) {
                throw ValidationException::withMessages(['batch' => 'Solo se puede corregir una planilla cerrada que todavía no ha sido pagada.']);
            }

            $range = $this->producers->weekRange($locked->week_end->toDateString());
            $currentPrice = MilkPrice::query()
                ->effectiveOn($range['end'])
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();
            $oldBasePrice = (float) ($currentPrice?->price_per_liter ?? 0);
            $newBasePrice = (float) $pricePerLiter;
            $oldValues = [
                'base_price' => $oldBasePrice,
                'gross_amount' => $locked->gross_amount,
                'net_amount' => $locked->net_amount,
                'items' => $locked->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'price_per_liter' => $item->price_per_liter,
                    'gross_amount' => $item->gross_amount,
                    'net_amount' => $item->net_amount,
                ])->all(),
            ];

            $affectedItems = 0;
            foreach ($locked->items as $item) {
                if ($oldBasePrice > 0 && abs((float) $item->price_per_liter - $oldBasePrice) > 0.0001) {
                    continue;
                }

                $gross = round((float) $item->liters * $newBasePrice, 2);
                $item->update([
                    'price_per_liter' => $pricePerLiter,
                    'gross_amount' => $gross,
                    'net_amount' => round($gross - (float) $item->advance_amount, 2),
                ]);
                $affectedItems++;
            }

            $route = Route::query()->orderBy('id')->first();
            $companyId = $currentPrice?->company_id ?? $route?->company_id;
            $plantId = $currentPrice?->plant_id ?? $route?->plant_id;
            if (! $companyId) {
                throw ValidationException::withMessages(['price_per_liter' => 'No se encontró la empresa de la planilla.']);
            }

            MilkPrice::query()->updateOrCreate([
                'company_id' => $companyId,
                'plant_id' => $plantId,
                'effective_from' => $range['start'],
                'effective_until' => $range['end'],
            ], [
                'price_per_liter' => $pricePerLiter,
                'active' => true,
            ]);

            $locked->update([
                'gross_amount' => $locked->items()->sum('gross_amount'),
                'advance_amount' => $locked->items()->sum('advance_amount'),
                'net_amount' => $locked->items()->sum('net_amount'),
                'notes' => trim(implode("\n", array_filter([
                    $locked->notes,
                    'Corrección de precio: '.$reason,
                ]))),
            ]);

            AuditEvent::query()->create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'plant_id' => $plantId,
                'entity_type' => ProducerPaymentBatch::class,
                'entity_id' => $locked->id,
                'action' => 'price_revised',
                'description' => "Precio base corregido de C$ {$oldBasePrice} a C$ {$newBasePrice}. {$affectedItems} pagos recalculados. Motivo: {$reason}",
                'old_values' => $oldValues,
                'new_values' => [
                    'base_price' => $newBasePrice,
                    'gross_amount' => $locked->fresh()->gross_amount,
                    'net_amount' => $locked->fresh()->net_amount,
                    'affected_items' => $affectedItems,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            return $locked->refresh()->load('items');
        });
    }

    public function markPaid(ProducerPaymentBatch $batch, User $user): ProducerPaymentBatch
    {
        return DB::transaction(function () use ($batch, $user) {
            $locked = ProducerPaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== ProducerPaymentBatch::STATUS_APPROVED) {
                throw ValidationException::withMessages(['batch' => 'La planilla debe estar aprobada antes de registrarse como pagada.']);
            }

            $transaction = $this->finances->createTransaction([
                'type' => FinanceTransaction::TYPE_PAGO, 'concept' => "Pago productores {$locked->code}",
                'description' => "Planilla del {$locked->week_start->format('d/m/Y')} al {$locked->week_end->format('d/m/Y')}",
                'amount' => $locked->net_amount, 'transaction_date' => now()->toDateString(),
                'reference' => $locked->code, 'payee' => 'Productores Lácteos Quebar', 'payment_method' => 'efectivo',
            ]);
            foreach ([FinanceTransaction::STATUS_REVIEWED, FinanceTransaction::STATUS_APPROVED, FinanceTransaction::STATUS_PAID] as $status) {
                $transaction = $this->finances->transition($transaction, $status);
            }

            $locked->update(['status' => ProducerPaymentBatch::STATUS_PAID, 'paid_by' => $user->id, 'paid_at' => now(), 'finance_transaction_id' => $transaction->id]);
            $this->audit($locked, $user, 'paid', 'Planilla semanal marcada como pagada');

            return $locked->refresh();
        });
    }

    public function transition(ProducerPaymentBatch $batch, string $targetStatus, User $user): ProducerPaymentBatch
    {
        return DB::transaction(function () use ($batch, $targetStatus, $user): ProducerPaymentBatch {
            $locked = ProducerPaymentBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $allowed = [
                ProducerPaymentBatch::STATUS_CLOSED => ProducerPaymentBatch::STATUS_REVIEWED,
                ProducerPaymentBatch::STATUS_REVIEWED => ProducerPaymentBatch::STATUS_APPROVED,
            ];

            if (($allowed[$locked->status] ?? null) !== $targetStatus) {
                throw ValidationException::withMessages([
                    'batch' => 'La transición solicitada no corresponde al estado actual de la planilla.',
                ]);
            }

            $attributes = ['status' => $targetStatus];
            if ($targetStatus === ProducerPaymentBatch::STATUS_REVIEWED) {
                $attributes += ['reviewed_by' => $user->id, 'reviewed_at' => now()];
            } else {
                $attributes += ['approved_by' => $user->id, 'approved_at' => now()];
            }

            $locked->update($attributes);
            $this->audit(
                $locked,
                $user,
                $targetStatus,
                $targetStatus === ProducerPaymentBatch::STATUS_REVIEWED
                    ? 'Planilla semanal revisada'
                    : 'Planilla semanal aprobada para pago',
            );

            return $locked->refresh();
        });
    }

    private function audit(ProducerPaymentBatch $batch, User $user, string $action, string $description): void
    {
        AuditEvent::query()->create(['user_id' => $user->id, 'entity_type' => ProducerPaymentBatch::class, 'entity_id' => $batch->id, 'action' => $action, 'description' => $description, 'new_values' => $batch->fresh()->toArray(), 'ip_address' => request()?->ip(), 'user_agent' => request()?->userAgent()]);
    }
}
