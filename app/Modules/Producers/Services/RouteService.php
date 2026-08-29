<?php

namespace App\Modules\Producers\Services;

use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreRouteRequest;
use Illuminate\Support\Facades\DB;

class RouteService
{
    public function create(StoreRouteRequest $request): Route
    {
        return DB::transaction(function () use ($request) {
            $company = $this->resolveCompany();
            $plant = $this->resolvePlant($company);

            $route = Route::create([
                'company_id' => $company->id,
                'plant_id' => $plant->id,
                'code' => $this->generateCode(),
                'name' => $request->validated('name'),
                'active' => true,
            ]);

            $this->auditCreate($route, $request->user());

            return $route;
        });
    }

    public function nextCode(): string
    {
        return $this->generateCode();
    }

    public function stats(): array
    {
        $total = Route::query()->count();
        $active = Route::query()->where('active', true)->count();
        $inactive = Route::query()->where('active', false)->count();
        $newThisMonth = Route::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newLastMonth = Route::query()
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->count();
        $totalPrevious = max($total - $newThisMonth, 0);

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

        Route::query()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->get(['created_at'])
            ->groupBy(fn (Route $route) => $route->created_at->format('Y-m'))
            ->each(function ($group, string $key) use ($monthly) {
                if ($monthly->has($key)) {
                    $month = $monthly->get($key);
                    $month['count'] = $group->count();
                    $monthly->put($key, $month);
                }
            });

        $today = now()->toDateString();
        $litersToday = (float) MilkCollection::query()
            ->whereDate('collection_date', $today)
            ->sum('liters');
        $price = (float) (MilkPrice::query()->effectiveOn(now())->value('price_per_liter') ?? 0);
        $recaudoToday = round($litersToday * $price, 2);
        $visitasToday = MilkCollection::query()
            ->whereDate('collection_date', $today)
            ->count();
        $solicitudes = Producer::query()
            ->whereDoesntHave('activeAssignment')
            ->count();
        $expectedVisits = max($active, 1);
        $cashOpen = now()->hour < 18;

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'new_this_month' => $newThisMonth,
            'trends' => [
                'total' => $this->percentTrend($total, $totalPrevious),
                'new_this_month' => $this->percentTrend($newThisMonth, $newLastMonth),
            ],
            'monthly' => $monthly->values()->all(),
            'recaudo' => $recaudoToday,
            'visitas' => $visitasToday,
            'solicitudes' => $solicitudes,
            'cash' => [
                'open' => $cashOpen,
                'status' => $cashOpen ? 'Caja abierta' : 'Caja cerrada',
                'recaudo' => $recaudoToday,
                'liters' => $litersToday,
                'visitas' => $visitasToday,
                'expected_visits' => $expectedVisits,
                'progress' => min(100, (int) round(($visitasToday / $expectedVisits) * 100)),
            ],
        ];
    }

    protected function percentTrend(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            return $current > 0
                ? ['direction' => 'up', 'value' => 100]
                : null;
        }

        $change = round((($current - $previous) / $previous) * 100, 1);

        return [
            'direction' => $change >= 0 ? 'up' : 'down',
            'value' => abs($change),
        ];
    }

    public function update(Route $route, array $data): Route
    {
        return DB::transaction(function () use ($route, $data) {
            $oldValues = $route->getAttributes();
            $route->update($data);
            $newValues = $route->getAttributes();

            $this->auditUpdate($route, $oldValues, $newValues, request()->user());

            return $route->fresh();
        });
    }

    public function toggleActive(Route $route): Route
    {
        return $this->update($route, [
            'active' => ! $route->active,
        ]);
    }

    protected function generateCode(): string
    {
        $lastNumber = Route::withTrashed()
            ->where('code', 'like', 'RUT-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return sprintf('RUT-%04d', ($lastNumber ?? 0) + 1);
    }

    protected function resolveCompany(): Company
    {
        return Company::query()->first() ?? Company::create([
            'name' => 'Northlink LACT',
            'legal_name' => 'Northlink LACT',
            'tax_id' => 'N/A',
            'active' => true,
        ]);
    }

    protected function resolvePlant(Company $company): Plant
    {
        return Plant::query()->where('company_id', $company->id)->first()
            ?? Plant::create([
                'company_id' => $company->id,
                'name' => 'Planta principal',
                'code' => 'PLT-001',
                'active' => true,
            ]);
    }

    protected function auditCreate(Route $route, $user): void
    {
        AuditEvent::create([
            'user_id' => $user?->id,
            'company_id' => $route->company_id,
            'plant_id' => $route->plant_id,
            'entity_type' => Route::class,
            'entity_id' => $route->id,
            'action' => 'create',
            'description' => 'Creación de ruta',
            'new_values' => $route->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function auditUpdate(Route $route, array $oldValues, array $newValues, $user): void
    {
        AuditEvent::create([
            'user_id' => $user?->id,
            'company_id' => $route->company_id,
            'plant_id' => $route->plant_id,
            'entity_type' => Route::class,
            'entity_id' => $route->id,
            'action' => 'update',
            'description' => 'Actualización de ruta',
            'old_values' => array_diff_assoc($oldValues, $newValues),
            'new_values' => array_diff_assoc($newValues, $oldValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
