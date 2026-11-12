<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreBulkMilkCollectionRequest;
use App\Modules\Producers\Services\MilkCollectionService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BulkMilkCollectionController extends Controller
{
    public function __construct(private MilkCollectionService $collectionService) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('create_collections'), 403);

        $validated = $request->validate([
            'route_id' => ['nullable', 'integer'],
            'week_start' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $weekStart = CarbonImmutable::parse($validated['week_start'] ?? now())->startOfWeek(CarbonInterface::FRIDAY);
        $weekEnd = $weekStart->addDays(6);
        $routesQuery = $this->visibleRoutes($request);
        $routes = (clone $routesQuery)->orderBy('name')->get(['id', 'code', 'name']);
        $route = isset($validated['route_id'])
            ? (clone $routesQuery)->findOrFail($validated['route_id'])
            : $routes->first();

        $rows = collect();
        if ($route) {
            $assignments = $route->assignments()
                ->whereDate('assigned_at', '<=', $weekEnd)
                ->where(fn (Builder $query) => $query
                    ->whereNull('ended_at')
                    ->orWhereDate('ended_at', '>=', $weekStart))
                ->whereHas('producer', fn (Builder $query) => $query->where('active', true))
                ->with('producer:id,code,full_name')
                ->get()
                ->unique('producer_id')
                ->sortBy(fn ($assignment) => $assignment->producer->full_name, SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            $producerIds = $assignments->pluck('producer_id');
            $existing = MilkCollection::query()
                ->where('route_id', $route->id)
                ->whereIn('producer_id', $producerIds)
                ->whereBetween('collection_date', [$weekStart, $weekEnd])
                ->get()
                ->keyBy(fn (MilkCollection $collection) => $collection->producer_id.'|'.$collection->collection_date->toDateString());
            $lockedBatches = ProducerPaymentBatch::query()
                ->whereDate('week_start', '<=', $weekEnd)
                ->whereDate('week_end', '>=', $weekStart)
                ->whereHas('items', fn (Builder $query) => $query->whereIn('producer_id', $producerIds))
                ->with(['items' => fn ($query) => $query->whereIn('producer_id', $producerIds)])
                ->get();

            $rows = $assignments->map(function ($assignment) use ($existing, $lockedBatches, $weekStart): array {
                $producer = $assignment->producer;
                $cells = collect(range(0, 6))->mapWithKeys(function (int $offset) use ($assignment, $producer, $existing, $lockedBatches, $weekStart): array {
                    $date = $weekStart->addDays($offset);
                    $collection = $existing->get($producer->id.'|'.$date->toDateString());
                    $available = $date->greaterThanOrEqualTo($assignment->assigned_at)
                        && ($assignment->ended_at === null || $date->lessThanOrEqualTo($assignment->ended_at));
                    $locked = $lockedBatches->contains(fn (ProducerPaymentBatch $batch) => $date->betweenIncluded($batch->week_start, $batch->week_end)
                        && $batch->items->contains('producer_id', $producer->id));

                    return [$date->toDateString() => [
                        'liters' => $collection?->liters,
                        'exists' => $collection !== null,
                        'locked' => $locked,
                        'available' => $available,
                        'source' => $collection?->route_run_id ? 'sunmi' : ($collection ? 'manual' : null),
                    ]];
                });

                return [
                    'id' => $producer->id,
                    'code' => $producer->code,
                    'full_name' => $producer->full_name,
                    'cells' => $cells,
                ];
            });
        }

        return Inertia::render('Producers/Collections/BulkEntry', [
            'routes' => $routes,
            'selectedRoute' => $route?->only(['id', 'code', 'name']),
            'weekStart' => $weekStart->toDateString(),
            'days' => collect(range(0, 6))->map(fn (int $offset) => [
                'date' => $weekStart->addDays($offset)->toDateString(),
                'label' => ucfirst($weekStart->addDays($offset)->locale('es')->isoFormat('ddd D')),
            ]),
            'rows' => $rows,
        ]);
    }

    public function store(StoreBulkMilkCollectionRequest $request): RedirectResponse
    {
        $route = $this->visibleRoutes($request)->findOrFail($request->integer('route_id'));

        try {
            $created = $this->collectionService->createBulk($route, $request->validated('entries'), $request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return redirect()->route('collections.bulk.index', [
            'route_id' => $route->id,
            'week_start' => $request->validated('week_start'),
        ])->with('success', "Se registraron {$created} acopios manuales.");
    }

    private function visibleRoutes(Request $request): Builder
    {
        $query = Route::query()->where('active', true);

        if ($request->user()->is_admin) {
            return $query;
        }

        return $query
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('company_user')
                ->whereColumn('company_user.company_id', 'routes.company_id')
                ->where('company_user.user_id', $request->user()->id)
                ->where(fn ($scope) => $scope
                    ->whereNull('company_user.plant_id')
                    ->orWhereColumn('company_user.plant_id', 'routes.plant_id')));
    }
}
