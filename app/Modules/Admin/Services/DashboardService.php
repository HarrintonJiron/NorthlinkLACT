<?php

namespace App\Modules\Admin\Services;

use App\Models\RouteRun;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
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
        ];
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function routeStatus(string $today): SupportCollection
    {
        return Route::query()
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
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Route $route): array {
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
            });
    }

    /**
     * @return SupportCollection<int, array{date: string, day: string, liters: float}>
     */
    private function weeklyCollections($weekStart, $weekEnd): SupportCollection
    {
        $litersByDate = MilkCollection::query()
            ->whereBetween('collection_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->selectRaw('collection_date, SUM(liters) as liters')
            ->groupBy('collection_date')
            ->pluck('liters', 'collection_date');

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

        return $operations->take(6)->values()->all();
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
