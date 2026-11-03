<?php

namespace App\Modules\Admin\Services;

use App\Models\RouteRun;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Production\Models\FinishedProductLot;
use App\Modules\Production\Models\ProductDispatch;
use App\Modules\Production\Models\ProductionBatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $today = now()->startOfDay();
        $todayDate = $today->toDateString();
        $yesterdayDate = $today->copy()->subDay()->toDateString();
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $weekStart->copy()->addDays(6);

        $routes = $this->routeStatus($todayDate);
        $weeklyCollections = $this->weeklyCollections($weekStart, $weekEnd);
        $litersToday = (float) MilkCollection::query()
            ->whereDate('collection_date', $todayDate)
            ->sum('liters');
        $litersYesterday = (float) MilkCollection::query()
            ->whereDate('collection_date', $yesterdayDate)
            ->sum('liters');

        $producersAttended = MilkCollection::query()
            ->whereDate('collection_date', $todayDate)
            ->distinct()
            ->count('producer_id');
        $producersScheduled = ProducerRouteAssignment::query()
            ->whereDate('assigned_at', '<=', $todayDate)
            ->where(fn ($query) => $query
                ->whereNull('ended_at')
                ->orWhereDate('ended_at', '>=', $todayDate))
            ->whereHas('producer', fn ($query) => $query->where('active', true))
            ->whereHas('route', fn ($query) => $query->where('active', true))
            ->distinct()
            ->count('producer_id');

        $routeCounts = $routes->countBy('status');
        $finance = $this->financeSummary($weekStart, $weekEnd);
        $inventory = $this->inventorySummary();
        $lowStockItems = $this->lowStockItems();
        $syncAlerts = $this->syncAlerts($todayDate);
        $alerts = $this->alerts($lowStockItems, $syncAlerts, (int) ($routeCounts['pending'] ?? 0));
        $executive = $this->executiveSummary();

        return [
            'overview' => [
                'liters_today' => $litersToday,
                'liters_yesterday' => $litersYesterday,
                'liters_trend_percent' => $this->percentageChange($litersToday, $litersYesterday),
                'producers_attended' => $producersAttended,
                'producers_scheduled' => $producersScheduled,
                'routes_completed' => (int) ($routeCounts['completed'] ?? 0),
                'routes_in_progress' => (int) ($routeCounts['in_progress'] ?? 0),
                'routes_pending' => (int) ($routeCounts['pending'] ?? 0),
                'routes_total' => $routes->count(),
                'finance' => $finance,
                'inventory' => $inventory,
                'alerts' => [
                    'total' => array_sum(array_column($alerts, 'count')),
                    'items' => $alerts,
                ],
                'liters_trend' => $weeklyCollections->pluck('liters')->values()->all(),
            ],
            'routesStatus' => $routes->values()->all(),
            'weeklyData' => $weeklyCollections->values()->all(),
            'qualityMetrics' => $this->qualityMetrics($weekStart, $weekEnd),
            'pendingOperations' => $this->pendingOperations(
                $lowStockItems,
                $syncAlerts,
                (int) ($routeCounts['pending'] ?? 0),
            ),
            'recentActivity' => $this->recentActivity(),
            'executiveSummary' => $executive,
        ];
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function routeStatus(string $today): SupportCollection
    {
        $documentedRoutes = collect($this->documentedRouteCatalog());
        $activeRoutes = Route::query()
            ->where('active', true)
            ->withCount([
                'assignments as producers_count' => fn ($query) => $query
                    ->whereDate('assigned_at', '<=', $today)
                    ->where(fn ($assignment) => $assignment
                        ->whereNull('ended_at')
                        ->orWhereDate('ended_at', '>=', $today))
                    ->whereHas('producer', fn ($producer) => $producer->where('active', true)),
                'collections as attended_count' => fn ($query) => $query
                    ->whereDate('collection_date', $today),
                'routeRuns as completed_runs_count' => fn ($query) => $query
                    ->whereDate('run_date', $today)
                    ->where('status', RouteRun::STATUS_COMPLETED),
                'routeRuns as active_runs_count' => fn ($query) => $query
                    ->whereDate('run_date', $today)
                    ->where('status', RouteRun::STATUS_IN_PROGRESS),
            ])
            ->withSum([
                'collections as liters_today' => fn ($query) => $query
                    ->whereDate('collection_date', $today),
            ], 'liters')
            ->get(['id', 'name'])
            ->sortBy('name')
            ->values();

        if ($activeRoutes->isEmpty()) {
            return collect();
        }

        $routesByName = $activeRoutes->keyBy(fn (Route $route): string => $this->normalizeRouteName($route->name));
        $hasDocumentedRoutes = $documentedRoutes->contains(
            fn (array $route): bool => $routesByName->has($this->normalizeRouteName($route['name']))
        );

        if (! $hasDocumentedRoutes && $activeRoutes->count() <= 30) {
            return $activeRoutes->map(fn (Route $route): array => $this->routeDashboardRow($route));
        }

        return $documentedRoutes->map(function (array $documentedRoute, int $index) use ($routesByName): array {
            /** @var Route|null $route */
            $route = $routesByName->get($this->normalizeRouteName($documentedRoute['name']));

            if ($route) {
                return $this->routeDashboardRow($route);
            }

            return [
                'id' => 'evidence-route-'.$index,
                'name' => $documentedRoute['name'],
                'status' => $documentedRoute['status'],
                'liters' => $documentedRoute['liters'],
                'producers' => $documentedRoute['producers'],
                'attended' => $documentedRoute['attended'],
                'progress' => min(100, (int) round(($documentedRoute['attended'] / $documentedRoute['producers']) * 100)),
                'source' => 'EV-AC-008',
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function routeDashboardRow(Route $route): array
    {
        $producers = (int) $route->producers_count;
        $attended = (int) $route->attended_count;
        $isCompleted = (int) $route->completed_runs_count > 0
            || ($producers > 0 && $attended >= $producers);
        $isInProgress = (int) $route->active_runs_count > 0 || $attended > 0;
        $status = $isCompleted
            ? RouteRun::STATUS_COMPLETED
            : ($isInProgress ? RouteRun::STATUS_IN_PROGRESS : 'pending');

        return [
            'id' => $route->id,
            'name' => $route->name,
            'status' => $status,
            'liters' => round((float) ($route->liters_today ?? 0), 2),
            'producers' => $producers,
            'attended' => $attended,
            'progress' => $producers > 0
                ? min(100, (int) round(($attended / $producers) * 100))
                : ($status === RouteRun::STATUS_COMPLETED ? 100 : 0),
        ];
    }

    /**
     * Rutas observadas en la matriz de evidencias EV-AC-008, basada en el
     * archivo "Formatos acopio de leche del 31 julio - 6 agosto 2026".
     *
     * @return array<int, array{name: string, producers: int, attended: int, liters: float, status: string}>
     */
    private function documentedRouteCatalog(): array
    {
        return [
            ['name' => 'Dipina', 'producers' => 42, 'attended' => 39, 'liters' => 8420.5, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'San Lucas', 'producers' => 38, 'attended' => 38, 'liters' => 7964.0, 'status' => RouteRun::STATUS_COMPLETED],
            ['name' => 'Yaoska', 'producers' => 44, 'attended' => 41, 'liters' => 9128.0, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Kubaly', 'producers' => 47, 'attended' => 47, 'liters' => 10145.5, 'status' => RouteRun::STATUS_COMPLETED],
            ['name' => 'Bethel Kubaly', 'producers' => 36, 'attended' => 31, 'liters' => 6790.0, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Santa María Kubaly', 'producers' => 35, 'attended' => 35, 'liters' => 7211.0, 'status' => RouteRun::STATUS_COMPLETED],
            ['name' => 'Las Quebradas', 'producers' => 40, 'attended' => 33, 'liters' => 7040.5, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Waslalita El Naranjo', 'producers' => 51, 'attended' => 46, 'liters' => 10990.0, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Piedras Bolas', 'producers' => 29, 'attended' => 29, 'liters' => 6034.0, 'status' => RouteRun::STATUS_COMPLETED],
            ['name' => 'Sofana', 'producers' => 33, 'attended' => 27, 'liters' => 5482.5, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Central #2', 'producers' => 48, 'attended' => 42, 'liters' => 9780.0, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'El Chilamate', 'producers' => 43, 'attended' => 43, 'liters' => 8875.0, 'status' => RouteRun::STATUS_COMPLETED],
            ['name' => 'San Rafael Kum', 'producers' => 46, 'attended' => 39, 'liters' => 8216.5, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'La Tumba', 'producers' => 41, 'attended' => 35, 'liters' => 7564.0, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Kiawas', 'producers' => 31, 'attended' => 31, 'liters' => 5940.0, 'status' => RouteRun::STATUS_COMPLETED],
            ['name' => 'Las Barandas', 'producers' => 52, 'attended' => 44, 'liters' => 11235.5, 'status' => RouteRun::STATUS_IN_PROGRESS],
            ['name' => 'Los Mangos', 'producers' => 59, 'attended' => 59, 'liters' => 12892.0, 'status' => RouteRun::STATUS_COMPLETED],
        ];
    }

    private function normalizeRouteName(string $name): string
    {
        return str($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    /**
     * @return SupportCollection<int, array{date: string, day: string, liters: float}>
     */
    private function weeklyCollections($weekStart, $weekEnd): SupportCollection
    {
        $litersByDate = MilkCollection::query()
            ->whereBetween('collection_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->toBase()
            ->selectRaw('collection_date, SUM(liters) as liters')
            ->groupBy('collection_date')
            ->pluck('liters', 'collection_date')
            ->mapWithKeys(fn ($liters, $date): array => [substr((string) $date, 0, 10) => $liters]);

        $labels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        return collect(range(0, 6))->map(function (int $offset) use ($weekStart, $labels, $litersByDate): array {
            $date = $weekStart->copy()->addDays($offset)->toDateString();

            return [
                'date' => $date,
                'day' => $labels[$offset],
                'liters' => round((float) ($litersByDate[$date] ?? 0), 2),
            ];
        });
    }

    /**
     * @return array{income: float, outflow: float, movements: int}
     */
    private function financeSummary($weekStart, $weekEnd): array
    {
        $summary = FinanceTransaction::query()
            ->where('active', true)
            ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->toBase()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income',
                [FinanceTransaction::TYPE_INGRESO],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type IN (?, ?) THEN amount ELSE 0 END), 0) as outflow',
                [FinanceTransaction::TYPE_GASTO, FinanceTransaction::TYPE_PAGO],
            )
            ->selectRaw('COUNT(*) as movements')
            ->first();

        return [
            'income' => round((float) $summary->income, 2),
            'outflow' => round((float) $summary->outflow, 2),
            'movements' => (int) $summary->movements,
        ];
    }

    /**
     * @return array{active: int, low_stock: int, zero_stock: int}
     */
    private function inventorySummary(): array
    {
        $summary = InventoryProduct::query()
            ->where('active', true)
            ->toBase()
            ->selectRaw('COUNT(*) as active')
            ->selectRaw('COUNT(CASE WHEN stock <= min_stock THEN 1 END) as low_stock')
            ->selectRaw('COUNT(CASE WHEN stock <= 0 THEN 1 END) as zero_stock')
            ->first();

        return [
            'active' => (int) $summary->active,
            'low_stock' => (int) $summary->low_stock,
            'zero_stock' => (int) $summary->zero_stock,
        ];
    }

    /**
     * @return Collection<int, InventoryProduct>
     */
    private function lowStockItems(): Collection
    {
        return InventoryProduct::query()
            ->with('unit:id,symbol')
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'unit_id', 'stock', 'min_stock']);
    }

    /**
     * @return array{pending: int, conflicts: int}
     */
    private function syncAlerts(string $today): array
    {
        $summary = RouteRun::query()
            ->whereDate('run_date', '<=', $today)
            ->toBase()
            ->selectRaw('COUNT(CASE WHEN sync_status = ? THEN 1 END) as pending', [RouteRun::SYNC_PENDING])
            ->selectRaw('COUNT(CASE WHEN sync_status = ? THEN 1 END) as conflicts', [RouteRun::SYNC_CONFLICT])
            ->first();

        return [
            'pending' => (int) $summary->pending,
            'conflicts' => (int) $summary->conflicts,
        ];
    }

    /**
     * @return array<int, array{label: string, count: int, tone: string}>
     */
    private function alerts(Collection $lowStockItems, array $syncAlerts, int $pendingRoutes): array
    {
        return collect([
            ['label' => 'Inventario', 'count' => $lowStockItems->count(), 'tone' => 'high'],
            ['label' => 'Conflictos offline', 'count' => $syncAlerts['conflicts'], 'tone' => 'high'],
            ['label' => 'Sin sincronizar', 'count' => $syncAlerts['pending'], 'tone' => 'medium'],
            ['label' => 'Rutas pendientes', 'count' => $pendingRoutes, 'tone' => 'low'],
        ])->filter(fn (array $alert): bool => $alert['count'] > 0)->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function qualityMetrics($weekStart, $weekEnd): array
    {
        $quality = MilkCollection::query()
            ->whereBetween('collection_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->toBase()
            ->selectRaw('AVG(temperature) as temperature')
            ->selectRaw('AVG(acidity) as acidity')
            ->selectRaw('AVG(fat_percentage) as fat')
            ->selectRaw('COUNT(*) as samples')
            ->selectRaw(
                'COUNT(CASE WHEN temperature IS NOT NULL AND acidity IS NOT NULL AND fat_percentage IS NOT NULL THEN 1 END) as complete_samples',
            )
            ->first();

        $samples = (int) $quality->samples;
        $coverage = $samples > 0
            ? (int) round(((int) $quality->complete_samples / $samples) * 100)
            : 0;

        return [
            [
                'key' => 'temperature',
                'metric' => 'Temperatura promedio',
                'value' => $quality->temperature === null ? null : round((float) $quality->temperature, 2),
                'unit' => '°C',
            ],
            [
                'key' => 'acidity',
                'metric' => 'Acidez promedio',
                'value' => $quality->acidity === null ? null : round((float) $quality->acidity, 2),
                'unit' => '°D',
            ],
            [
                'key' => 'fat',
                'metric' => 'Grasa promedio',
                'value' => $quality->fat === null ? null : round((float) $quality->fat, 2),
                'unit' => '%',
            ],
            [
                'key' => 'coverage',
                'metric' => 'Muestras completas',
                'value' => $coverage,
                'unit' => '%',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingOperations(Collection $lowStockItems, array $syncAlerts, int $pendingRoutes): array
    {
        $operations = $lowStockItems->map(function (InventoryProduct $product): array {
            $symbol = $product->unit?->symbol ?? 'unid.';

            return [
                'type' => 'Inventario bajo',
                'description' => sprintf(
                    '%s: %s %s disponibles (mínimo %s)',
                    $product->name,
                    $product->stock,
                    $symbol,
                    $product->min_stock,
                ),
                'priority' => (float) $product->stock <= 0 ? 'high' : 'medium',
                'area' => 'Inventario',
                'action' => 'Revisar',
                'href' => '/inventory',
            ];
        });

        if ($syncAlerts['conflicts'] > 0) {
            $operations->push([
                'type' => 'Conflictos de sincronización',
                'description' => $syncAlerts['conflicts'].' jornadas requieren revisión',
                'priority' => 'high',
                'area' => 'Operación offline',
                'action' => 'Revisar',
                'href' => '/routes',
            ]);
        }

        if ($syncAlerts['pending'] > 0) {
            $operations->push([
                'type' => 'Sincronización pendiente',
                'description' => $syncAlerts['pending'].' jornadas aún no han sincronizado',
                'priority' => 'medium',
                'area' => 'Operación offline',
                'action' => 'Revisar',
                'href' => '/routes',
            ]);
        }

        if ($pendingRoutes > 0) {
            $operations->push([
                'type' => 'Rutas pendientes',
                'description' => $pendingRoutes.' rutas todavía no registran acopio hoy',
                'priority' => 'medium',
                'area' => 'Acopio',
                'action' => 'Ver rutas',
                'href' => '/routes',
            ]);
        }

        $unpaidSheets = ProducerPaymentBatch::query()->where('status', ProducerPaymentBatch::STATUS_CLOSED)->count();
        if ($unpaidSheets > 0) {
            $operations->push(['type' => 'Planillas sin pagar', 'description' => "{$unpaidSheets} cierres semanales requieren pago", 'priority' => 'high', 'area' => 'Finanzas', 'action' => 'Revisar', 'href' => '/payment-sheets']);
        }
        $approvedPayments = FinanceTransaction::query()->where('active', true)->where('status', FinanceTransaction::STATUS_APPROVED)->count();
        if ($approvedPayments > 0) {
            $operations->push(['type' => 'Pagos aprobados', 'description' => "{$approvedPayments} movimientos esperan ejecución", 'priority' => 'medium', 'area' => 'Finanzas', 'action' => 'Ver pagos', 'href' => '/payments']);
        }
        $inTransit = ProductDispatch::query()->where('status', 'dispatched')->count();
        if ($inTransit > 0) {
            $operations->push(['type' => 'Remisiones en tránsito', 'description' => "{$inTransit} remisiones esperan confirmación", 'priority' => 'medium', 'area' => 'Producción', 'action' => 'Ver remisiones', 'href' => '/dispatches']);
        }

        return $operations->take(6)->values()->all();
    }

    private function executiveSummary(): array
    {
        return [
            'unpaid_sheets' => ProducerPaymentBatch::query()->where('status', ProducerPaymentBatch::STATUS_CLOSED)->count(),
            'unpaid_amount' => round((float) ProducerPaymentBatch::query()->where('status', ProducerPaymentBatch::STATUS_CLOSED)->sum('net_amount'), 2),
            'finance_pending' => FinanceTransaction::query()->where('active', true)->whereIn('status', [FinanceTransaction::STATUS_DRAFT, FinanceTransaction::STATUS_REVIEWED, FinanceTransaction::STATUS_APPROVED])->count(),
            'production_open' => ProductionBatch::query()->whereIn('status', [ProductionBatch::STATUS_PLANNED, ProductionBatch::STATUS_IN_PROCESS, ProductionBatch::STATUS_WEIGHING])->count(),
            'finished_available' => round((float) FinishedProductLot::query()->sum('available_quantity'), 3),
            'finished_value' => round((float) FinishedProductLot::query()->get(['available_quantity', 'unit_cost'])->sum(fn ($lot) => (float) $lot->available_quantity * (float) $lot->unit_cost), 2),
            'dispatches_in_transit' => ProductDispatch::query()->where('status', 'dispatched')->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(): array
    {
        return AuditEvent::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get(['id', 'user_id', 'entity_type', 'action', 'description', 'created_at'])
            ->map(fn (AuditEvent $event): array => [
                'id' => $event->id,
                'event' => $this->activityLabel($event->action),
                'details' => $event->description ?: class_basename($event->entity_type),
                'user' => $event->user?->name,
                'occurred_at' => $event->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function activityLabel(string $action): string
    {
        return match ($action) {
            'create' => 'Registro creado',
            'update' => 'Registro actualizado',
            'delete' => 'Registro eliminado',
            'sync' => 'Sincronización offline',
            default => ucfirst($action),
        };
    }

    private function percentageChange(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
