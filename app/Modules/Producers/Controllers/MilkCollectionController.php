<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\ListMilkCollectionsRequest;
use App\Modules\Producers\Requests\ResolveMilkCollectionCorrectionRequest;
use App\Modules\Producers\Requests\StoreMilkCollectionRequest;
use App\Modules\Producers\Services\MilkCollectionService;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class MilkCollectionController extends Controller
{
    public function __construct(
        private MilkCollectionService $collectionService
    ) {}

    public function index(ListMilkCollectionsRequest $request): Response
    {
        $filters = $request->validated();
        $query = $this->filteredCollections($filters);

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total_records')
            ->selectRaw('COALESCE(SUM(liters), 0) as total_liters')
            ->selectRaw("COUNT(CASE WHEN sync_status = 'synced' THEN 1 END) as synced_records")
            ->selectRaw("COUNT(CASE WHEN sync_status = 'conflict' THEN 1 END) as conflict_records")
            ->selectRaw('MAX(synced_at) as last_synced_at')
            ->first();

        $collections = (clone $query)
            ->with([
                'producer:id,code,full_name,phone',
                'route:id,code,name',
                'company:id,name',
                'plant:id,name,code',
                'device:id,name,platform',
            ])
            ->orderByDesc('collection_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $routes = Route::query()
            ->where('active', true)
            ->withCount(['activeAssignments as scheduled_producers_count' => fn ($query) => $query
                ->whereHas('producer', fn ($producer) => $producer->where('active', true))])
            ->whereExists(fn ($routeAccess) => $routeAccess
                ->selectRaw('1')
                ->from('company_user')
                ->whereColumn('company_user.company_id', 'routes.company_id')
                ->where('company_user.user_id', $request->user()->id)
                ->where(fn ($scope) => $scope
                    ->whereNull('company_user.plant_id')
                    ->orWhereColumn('company_user.plant_id', 'routes.plant_id')))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $progressDate = $filters['date_to'] ?? now()->toDateString();
        $visitsByRoute = $this->visibleCollections()
            ->whereDate('collection_date', $progressDate)
            ->selectRaw('route_id, COUNT(DISTINCT producer_id) as visits, COALESCE(SUM(liters), 0) as liters')
            ->groupBy('route_id')
            ->get()
            ->keyBy('route_id');

        $routeProgress = $routes->map(function (Route $route) use ($visitsByRoute): array {
            $scheduled = (int) $route->scheduled_producers_count;
            $visits = (int) ($visitsByRoute->get($route->id)?->visits ?? 0);

            return [
                'id' => $route->id,
                'code' => $route->code,
                'name' => $route->name,
                'scheduled' => $scheduled,
                'visited' => $visits,
                'pending' => max(0, $scheduled - $visits),
                'liters' => number_format((float) ($visitsByRoute->get($route->id)?->liters ?? 0), 2, '.', ''),
                'percent' => $scheduled > 0 ? min(100, (int) round(($visits / $scheduled) * 100)) : 0,
                'completed' => $scheduled > 0 && $visits >= $scheduled,
            ];
        })->values();

        return Inertia::render('Producers/Collections/Index', [
            'collections' => $collections,
            'filters' => $filters,
            'routes' => $routes,
            'routeProgress' => $routeProgress,
            'progressDate' => $progressDate,
            'summary' => [
                'total_records' => (int) ($summary?->total_records ?? 0),
                'total_liters' => number_format((float) ($summary?->total_liters ?? 0), 2, '.', ''),
                'synced_records' => (int) ($summary?->synced_records ?? 0),
                'conflict_records' => (int) ($summary?->conflict_records ?? 0),
                'last_synced_at' => $summary?->last_synced_at,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Producers/Collections/Create');
    }

    public function store(StoreMilkCollectionRequest $request)
    {
        try {
            $collection = $this->collectionService->createWithLock($request);

            return redirect()->route('collections.index')
                ->with('success', 'Acopio registrado exitosamente.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(MilkCollection $collection)
    {
        abort_unless($this->visibleCollections()->whereKey($collection->id)->exists(), 404);

        $collection->load(['producer', 'route', 'company', 'plant', 'collectedBy', 'verifiedBy', 'device', 'correctionRequestedBy', 'correctionResolvedBy']);

        return Inertia::render('Producers/Collections/Show', [
            'collection' => $collection,
        ]);
    }

    public function resolveCorrection(ResolveMilkCollectionCorrectionRequest $request, MilkCollection $collection)
    {
        abort_unless($this->visibleCollections()->whereKey($collection->id)->exists(), 404);

        try {
            $this->collectionService->resolveCorrection($collection, $request->validated(), $request->user());
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['correction' => $exception->getMessage()]);
        }

        return back()->with('success', 'Acopio corregido y solicitud cerrada con trazabilidad.');
    }

    private function visibleCollections(): Builder
    {
        $user = request()->user();

        return MilkCollection::query()
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('company_user')
                ->whereColumn('company_user.company_id', 'milk_collections.company_id')
                ->where('company_user.user_id', $user->id)
                ->where(fn ($scope) => $scope
                    ->whereNull('company_user.plant_id')
                    ->orWhereColumn('company_user.plant_id', 'milk_collections.plant_id')));
    }

    /** @param array<string, mixed> $filters */
    private function filteredCollections(array $filters): Builder
    {
        return $this->visibleCollections()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->whereHas('producer', fn (Builder $producer) => $producer
                            ->where('full_name', 'ilike', "%{$search}%")
                            ->orWhere('code', 'ilike', "%{$search}%"))
                        ->orWhereHas('route', fn (Builder $route) => $route
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('code', 'ilike', "%{$search}%"));
                });
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query
                ->whereDate('collection_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query
                ->whereDate('collection_date', '<=', $date))
            ->when($filters['route_id'] ?? null, fn (Builder $query, int $routeId) => $query
                ->where('route_id', $routeId))
            ->when($filters['visit_status'] ?? null, fn (Builder $query, string $status) => $query
                ->where('visit_status', $status))
            ->when($filters['sync_status'] ?? null, fn (Builder $query, string $status) => $query
                ->where('sync_status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $source === 'offline'
                ? $query->whereNotNull('route_run_id')
                : $query->whereNull('route_run_id'));
    }
}
