<?php

namespace App\Modules\Finanzas\Services;

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Finanzas\Models\FinanceCategory;
use App\Modules\Finanzas\Models\FinanceTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanzasService
{
    public function stats(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $base = FinanceTransaction::query()->where('active', true);

        $total = (clone $base)->count();
        $gastos = (float) (clone $base)->where('type', FinanceTransaction::TYPE_GASTO)->sum('amount');
        $pagos = (float) (clone $base)->where('type', FinanceTransaction::TYPE_PAGO)->sum('amount');
        $ingresos = (float) (clone $base)->where('type', FinanceTransaction::TYPE_INGRESO)->sum('amount');

        $monthlyBase = (clone $base)->whereBetween('transaction_date', [$startOfMonth, $endOfMonth]);
        $gastosMes = (float) (clone $monthlyBase)->where('type', FinanceTransaction::TYPE_GASTO)->sum('amount');
        $pagosMes = (float) (clone $monthlyBase)->where('type', FinanceTransaction::TYPE_PAGO)->sum('amount');
        $ingresosMes = (float) (clone $monthlyBase)->where('type', FinanceTransaction::TYPE_INGRESO)->sum('amount');
        $movimientosMes = (clone $monthlyBase)->count();

        $salidasMes = $gastosMes + $pagosMes;
        $balanceMes = $ingresosMes - $salidasMes;

        $monthLabels = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $monthly = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $monthly->put($date->format('Y-m'), [
                'key' => $date->format('Y-m'),
                'label' => $monthLabels[(int) $date->format('n')],
                'ingresos' => 0.0,
                'salidas' => 0.0,
            ]);
        }

        FinanceTransaction::query()
            ->where('active', true)
            ->where('transaction_date', '>=', now()->startOfMonth()->subMonths(11))
            ->get(['type', 'amount', 'transaction_date'])
            ->groupBy(fn (FinanceTransaction $tx) => $tx->transaction_date->format('Y-m'))
            ->each(function ($group, string $key) use ($monthly) {
                if (! $monthly->has($key)) {
                    return;
                }

                $month = $monthly->get($key);
                $month['ingresos'] = (float) $group
                    ->where('type', FinanceTransaction::TYPE_INGRESO)
                    ->sum('amount');
                $month['salidas'] = (float) $group
                    ->whereIn('type', [FinanceTransaction::TYPE_GASTO, FinanceTransaction::TYPE_PAGO])
                    ->sum('amount');
                $monthly->put($key, $month);
            });

        $byType = collect([
            FinanceTransaction::TYPE_GASTO => $gastos,
            FinanceTransaction::TYPE_PAGO => $pagos,
            FinanceTransaction::TYPE_INGRESO => $ingresos,
        ])->map(fn (float $amount, string $type) => [
            'type' => $type,
            'label' => $this->typeLabel($type),
            'amount' => $amount,
        ])->values()->all();

        return [
            'total' => $total,
            'gastos' => $gastos,
            'pagos' => $pagos,
            'ingresos' => $ingresos,
            'gastos_mes' => $gastosMes,
            'pagos_mes' => $pagosMes,
            'ingresos_mes' => $ingresosMes,
            'salidas_mes' => $salidasMes,
            'balance_mes' => $balanceMes,
            'movimientos_mes' => $movimientosMes,
            'monthly' => $monthly->values()->all(),
            'by_type' => $byType,
        ];
    }

    public function typeLabel(string $type): string
    {
        return match ($type) {
            FinanceTransaction::TYPE_PAGO => 'Pagos',
            FinanceTransaction::TYPE_INGRESO => 'Ingresos',
            default => 'Gastos',
        };
    }

    public function createTransaction(array $data): FinanceTransaction
    {
        return DB::transaction(function () use ($data) {
            return FinanceTransaction::query()->create([
                'code' => $this->allocateNextCode(),
                'type' => $data['type'],
                'category_id' => $data['category_id'] ?? null,
                'concept' => $data['concept'],
                'description' => $data['description'] ?? null,
                'amount' => (float) $data['amount'],
                'transaction_date' => $data['transaction_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'payee' => $data['payee'] ?? null,
                'notes' => $data['notes'] ?? null,
                'active' => array_key_exists('active', $data) ? (bool) $data['active'] : true,
                'status' => FinanceTransaction::STATUS_DRAFT,
            ]);
        });
    }

    public function updateTransaction(FinanceTransaction $transaction, array $data): FinanceTransaction
    {
        if ($transaction->status !== FinanceTransaction::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'transaction' => 'Solo los movimientos en borrador pueden editarse. Anúlalo y registra uno nuevo si necesitas corregirlo.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $data) {
            $old = $transaction->toArray();
            unset($data['code'], $data['status']);
            $data['amount'] = (float) $data['amount'];
            $transaction->update($data);
            $this->audit($transaction, 'updated', 'Movimiento financiero actualizado', $old);

            return $transaction->refresh();
        });
    }

    public function transition(FinanceTransaction $transaction, string $status, ?string $reason = null): FinanceTransaction
    {
        return DB::transaction(function () use ($transaction, $status, $reason) {
            $locked = FinanceTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $allowed = [
                FinanceTransaction::STATUS_DRAFT => [FinanceTransaction::STATUS_REVIEWED, FinanceTransaction::STATUS_VOIDED],
                FinanceTransaction::STATUS_REVIEWED => [FinanceTransaction::STATUS_APPROVED, FinanceTransaction::STATUS_VOIDED],
                FinanceTransaction::STATUS_APPROVED => [FinanceTransaction::STATUS_PAID, FinanceTransaction::STATUS_VOIDED],
                FinanceTransaction::STATUS_PAID => [FinanceTransaction::STATUS_RECONCILED, FinanceTransaction::STATUS_VOIDED],
                FinanceTransaction::STATUS_RECONCILED => [],
                FinanceTransaction::STATUS_VOIDED => [],
            ];

            if (! in_array($status, $allowed[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'La transición de estado solicitada no está permitida.',
                ]);
            }

            $old = $locked->toArray();
            $updates = ['status' => $status];
            if ($status === FinanceTransaction::STATUS_APPROVED) {
                $updates += ['approved_by' => auth()->id(), 'approved_at' => now()];
            } elseif ($status === FinanceTransaction::STATUS_PAID) {
                $updates += ['paid_by' => auth()->id(), 'paid_at' => now()];
            } elseif ($status === FinanceTransaction::STATUS_RECONCILED) {
                $updates += ['reconciled_by' => auth()->id(), 'reconciled_at' => now()];
            } elseif ($status === FinanceTransaction::STATUS_VOIDED) {
                $updates += ['void_reason' => trim((string) $reason), 'active' => false];
            }

            $locked->update($updates);
            $this->audit($locked, 'status_changed', "Estado financiero cambiado a {$status}", $old);

            return $locked->refresh();
        });
    }

    private function audit(FinanceTransaction $transaction, string $action, string $description, ?array $old = null): void
    {
        AuditEvent::query()->create([
            'user_id' => auth()->id(),
            'entity_type' => FinanceTransaction::class,
            'entity_id' => $transaction->id,
            'action' => $action,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $transaction->fresh()?->toArray(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    protected function allocateNextCode(): string
    {
        return 'FIN-'.str_pad((string) ($this->lastTransactionNumber() + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function lastTransactionNumber(): int
    {
        $max = FinanceTransaction::withTrashed()
            ->where('code', 'like', 'FIN-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return (int) ($max ?? 0);
    }

    public function ensureDefaultCategories(): void
    {
        $defaults = [
            ['code' => 'OPER', 'name' => 'Operaciones', 'type' => FinanceTransaction::TYPE_GASTO],
            ['code' => 'PROD', 'name' => 'Productores', 'type' => FinanceTransaction::TYPE_PAGO],
            ['code' => 'SERV', 'name' => 'Servicios', 'type' => FinanceTransaction::TYPE_GASTO],
            ['code' => 'INS', 'name' => 'Insumos', 'type' => FinanceTransaction::TYPE_GASTO],
            ['code' => 'NOM', 'name' => 'Nómina', 'type' => FinanceTransaction::TYPE_PAGO],
            ['code' => 'MANT', 'name' => 'Mantenimiento', 'type' => FinanceTransaction::TYPE_GASTO],
            ['code' => 'VENT', 'name' => 'Ventas', 'type' => FinanceTransaction::TYPE_INGRESO],
            ['code' => 'OTRO', 'name' => 'Otros', 'type' => FinanceTransaction::TYPE_GASTO],
        ];

        foreach ($defaults as $category) {
            FinanceCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'active' => true,
                ]
            );
        }
    }
}
