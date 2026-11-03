<?php

namespace App\Modules\Producers\Services;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreRouteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RouteService
{
    public function create(StoreRouteRequest $request): Route
    {
        return DB::transaction(function () use ($request) {
            $company = $this->resolveCompany($request->user());
            $plant = $this->resolvePlant($company, $request->user());

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

    public function stats(User $user): array
    {
        $routes = $this->accessibleQuery($user);
        $total = (clone $routes)->count();
        $active = (clone $routes)->where('active', true)->count();
        $inactive = (clone $routes)->where('active', false)->count();
        $newThisMonth = (clone $routes)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newLastMonth = (clone $routes)
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

        (clone $routes)
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
        $routeIds = (clone $routes)->select('routes.id');
        $litersToday = (float) MilkCollection::query()
            ->whereIn('route_id', $routeIds)
            ->whereDate('collection_date', $today)
            ->sum('liters');
        $price = (float) (MilkPrice::query()
            ->whereIn('company_id', (clone $routes)->select('routes.company_id'))
            ->effectiveOn(now())
            ->value('price_per_liter') ?? 0);
        $recaudoToday = round($litersToday * $price, 2);
        $visitasToday = MilkCollection::query()
            ->whereIn('route_id', (clone $routes)->select('routes.id'))
            ->whereDate('collection_date', $today)
            ->count();
        $solicitudes = Producer::query()
            ->whereDoesntHave('activeAssignment')
            ->whereHas('assignments.route', fn (Builder $query) => $query
                ->whereIn('routes.id', (clone $routes)->select('routes.id')))
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

    public function accessibleQuery(User $user): Builder
    {
        $query = Route::query();

        if ($user->is_admin) {
            return $query;
        }

        return $query->whereExists(fn ($access) => $access
            ->selectRaw('1')
            ->from('company_user')
            ->whereColumn('company_user.company_id', 'routes.company_id')
            ->where('company_user.user_id', $user->id)
            ->where(fn ($plant) => $plant
                ->whereNull('company_user.plant_id')
                ->orWhereColumn('company_user.plant_id', 'routes.plant_id')));
    }

    public function userCanAccess(User $user, Route $route): bool
    {
        return $this->accessibleQuery($user)->whereKey($route->id)->exists();
    }

    protected function resolveCompany(User $user): Company
    {
        if (! $user->is_admin) {
            return $user->companies()->where('companies.active', true)->firstOrFail();
        }

        return Company::query()->first() ?? Company::create([
            'name' => 'Northlink Quebar',
            'legal_name' => 'Northlink Quebar',
            'tax_id' => 'N/A',
            'active' => true,
        ]);
    }

    protected function resolvePlant(Company $company, User $user): Plant
    {
        if (! $user->is_admin) {
            $plantId = $user->companies()
                ->where('companies.id', $company->id)
                ->value('company_user.plant_id');

            if ($plantId) {
                return Plant::query()
                    ->whereKey($plantId)
                    ->where('company_id', $company->id)
                    ->where('active', true)
                    ->firstOrFail();
            }
        }

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
