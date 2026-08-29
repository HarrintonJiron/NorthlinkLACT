<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreRouteCollectionRequest;
use App\Modules\Producers\Requests\StoreRouteRequest;
use App\Modules\Producers\Requests\UpdateRouteRequest;
use App\Modules\Producers\Services\MilkCollectionService;
use App\Modules\Producers\Services\RouteService;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function __construct(
        private RouteService $routeService,
        private MilkCollectionService $collectionService
    ) {}

    public function index()
    {
        // Temporalmente sin filtro por usuario durante desarrollo
        $routes = Route::with(['company', 'plant', 'rutero'])->latest()->get();

        return Inertia::render('Producers/Routes/Index', [
            'routes' => $routes,
            'nextCode' => $this->routeService->nextCode(),
            'stats' => $this->routeService->stats(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Producers/Routes/Create');
    }

    public function store(StoreRouteRequest $request)
    {
        $route = $this->routeService->create($request);

        return redirect()->route('routes.index')
            ->with('success', 'Ruta creada exitosamente.');
    }

    public function show(Route $route)
    {
        // Temporalmente sin authorize durante desarrollo
        // $this->authorize('view', $route);

        $route->load(['company', 'plant', 'rutero', 'activeAssignments.producer']);

        $today = now()->toDateString();
        $todayLiters = MilkCollection::query()
            ->where('route_id', $route->id)
            ->whereDate('collection_date', $today)
            ->pluck('liters', 'producer_id');

        return Inertia::render('Producers/Routes/Show', [
            'route' => $route,
            'today' => $today,
            'todayLiters' => $todayLiters,
            'routes' => Route::query()->where('active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateRouteRequest $request, Route $route)
    {
        $this->routeService->update($route, $request->validated());

        return redirect()->route('routes.show', $route)
            ->with('success', 'Ruta actualizada exitosamente.');
    }

    public function toggle(Route $route)
    {
        $route = $this->routeService->toggleActive($route);
        $status = $route->active ? 'activada' : 'desactivada';

        return redirect()->back()
            ->with('success', "Ruta {$status} exitosamente.");
    }

    public function storeCollection(StoreRouteCollectionRequest $request, Route $route)
    {
        $this->collectionService->recordForRoute($route, $request);

        return redirect()->back()
            ->with('success', 'Acopio del día registrado.');
    }
}
