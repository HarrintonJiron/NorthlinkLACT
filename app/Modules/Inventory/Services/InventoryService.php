<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function stats(): array
    {
        $total = InventoryProduct::query()->count();
        $active = InventoryProduct::query()->where('active', true)->count();
        $inactive = InventoryProduct::query()->where('active', false)->count();
        $lowStock = InventoryProduct::query()
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();
        $zeroStock = InventoryProduct::query()
            ->where('active', true)
            ->where('stock', '<=', 0)
            ->count();
        $newThisMonth = InventoryProduct::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

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
                'count' => 0,
            ]);
        }

        InventoryProduct::query()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->get(['created_at'])
            ->groupBy(fn (InventoryProduct $product) => $product->created_at->format('Y-m'))
            ->each(function ($group, string $key) use ($monthly) {
                if ($monthly->has($key)) {
                    $month = $monthly->get($key);
                    $month['count'] = $group->count();
                    $monthly->put($key, $month);
                }
            });

        $byUnit = InventoryUnit::query()
            ->where('active', true)
            ->withCount(['products' => fn ($query) => $query->whereNull('deleted_at')])
            ->orderByDesc('products_count')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'symbol'])
            ->map(fn (InventoryUnit $unit) => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'products' => $unit->products_count,
            ])
            ->values()
            ->all();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'low_stock' => $lowStock,
            'zero_stock' => $zeroStock,
            'units' => InventoryUnit::query()->where('active', true)->count(),
            'new_this_month' => $newThisMonth,
            'monthly' => $monthly->values()->all(),
            'by_unit' => $byUnit,
        ];
    }

    public function nextCode(): string
    {
        return 'PRD-'.str_pad((string) ($this->lastProductNumber() + 1), 4, '0', STR_PAD_LEFT);
    }

    public function createProduct(array $data): InventoryProduct
    {
        return DB::transaction(function () use ($data) {
            $payload = [
                'code' => $this->allocateNextCode(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'unit_id' => (int) $data['unit_id'],
                'stock' => (float) ($data['stock'] ?? 0),
                'min_stock' => (float) ($data['min_stock'] ?? 0),
                'expiration_date' => $data['expiration_date'] ?? null,
                'active' => array_key_exists('active', $data) ? (bool) $data['active'] : true,
            ];

            return InventoryProduct::query()->create($payload);
        });
    }

    public function createProductsBulk(array $items): int
    {
        return DB::transaction(function () use ($items) {
            $created = 0;

            foreach ($items as $item) {
                InventoryProduct::query()->create([
                    'code' => $this->allocateNextCode(),
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'unit_id' => $item['unit_id'],
                    'stock' => (float) ($item['stock'] ?? 0),
                    'min_stock' => (float) ($item['min_stock'] ?? 0),
                    'active' => true,
                ]);

                $created++;
            }

            return $created;
        });
    }

    protected function allocateNextCode(): string
    {
        return 'PRD-'.str_pad((string) ($this->lastProductNumber() + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function lastProductNumber(): int
    {
        $max = InventoryProduct::withTrashed()
            ->where('code', 'like', 'PRD-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return (int) ($max ?? 0);
    }

    public function ensureDefaultUnits(): void
    {
        $defaults = [
            ['code' => 'L', 'name' => 'Litro', 'symbol' => 'L'],
            ['code' => 'ML', 'name' => 'Mililitro', 'symbol' => 'ml'],
            ['code' => 'KG', 'name' => 'Kilogramo', 'symbol' => 'kg'],
            ['code' => 'G', 'name' => 'Gramo', 'symbol' => 'g'],
            ['code' => 'UND', 'name' => 'Unidad', 'symbol' => 'und'],
            ['code' => 'CJ', 'name' => 'Caja', 'symbol' => 'cja'],
            ['code' => 'GAL', 'name' => 'Galón', 'symbol' => 'gal'],
            ['code' => 'QQ', 'name' => 'Quintal', 'symbol' => 'qq'],
        ];

        foreach ($defaults as $unit) {
            InventoryUnit::query()->firstOrCreate(
                ['code' => $unit['code']],
                [
                    'name' => $unit['name'],
                    'symbol' => $unit['symbol'],
                    'active' => true,
                ]
            );
        }
    }
}
