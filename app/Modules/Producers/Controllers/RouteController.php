<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\AssignRuteroToRouteRequest;
use App\Modules\Producers\Requests\StoreRouteCollectionRequest;
use App\Modules\Producers\Requests\StoreRouteRequest;
use App\Modules\Producers\Requests\UpdateRouteRequest;
use App\Modules\Producers\Services\MilkCollectionService;
use App\Modules\Producers\Services\RouteService;
use App\Modules\Ruteros\Models\Rutero;
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

        $route->load(['company', 'plant', 'rutero']);

        return Inertia::render('Producers/Routes/Show', [
            'route' => $route,
            'availableRuteros' => $this->availableRuteros($route),
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

    public function assignRutero(AssignRuteroToRouteRequest $request, Route $route)
    {
        $rutero = Rutero::query()->findOrFail($request->validated('rutero_id'));

        if (! $rutero->active) {
            return redirect()->back()
                ->with('error', 'No puedes asignar un rutero inactivo.');
        }

        if ($rutero->route_id && (int) $rutero->route_id !== (int) $route->id) {
            return redirect()->back()
                ->with('error', 'Ese rutero ya está asignado a otra ruta.');
        }

        Rutero::query()
            ->where('route_id', $route->id)
            ->where('id', '!=', $rutero->id)
            ->update(['route_id' => null]);

        $rutero->update(['route_id' => $route->id]);

        return redirect()->route('routes.show', $route)
            ->with('success', 'Rutero asignado a la ruta.');
    }

    public function unassignRutero(Route $route)
    {
        Rutero::query()
            ->where('route_id', $route->id)
            ->update(['route_id' => null]);

        return redirect()->route('routes.show', $route)
            ->with('success', 'Rutero desasignado de la ruta.');
    }

    protected function availableRuteros(Route $route)
    {
        return Rutero::query()
            ->where('active', true)
            ->where(function ($query) use ($route) {
                $query->whereNull('route_id')
                    ->orWhere('route_id', $route->id);
            })
            ->orderBy('owner_name')
            ->get(['id', 'owner_name', 'driver_name', 'vehicle_plate', 'route_id']);
    }
}
