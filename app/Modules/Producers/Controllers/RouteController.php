<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\AssignRuteroToRouteRequest;
use App\Modules\Producers\Requests\MergeRoutesRequest;
use App\Modules\Producers\Requests\MoveRouteProducersRequest;
use App\Modules\Producers\Requests\SplitRouteRequest;
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
        $user = request()->user();
        $routes = $this->routeService->accessibleQuery($user)
            ->with(['company', 'plant', 'rutero'])
            ->latest()
            ->get();

        return Inertia::render('Producers/Routes/Index', [
            'routes' => $routes,
            'nextCode' => $this->routeService->nextCode(),
            'stats' => $this->routeService->stats($user),
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
        $this->ensureRouteAccess($route);

        $route->load(['company', 'plant', 'rutero', 'activeAssignments.producer']);

        return Inertia::render('Producers/Routes/Show', [
            'route' => $route,
            'availableRuteros' => $this->availableRuteros($route),
            'otherRoutes' => $this->routeService->accessibleQuery(request()->user())
                ->where('active', true)
                ->where('routes.id', '!=', $route->id)
                ->where('company_id', $route->company_id)
                ->where('plant_id', $route->plant_id)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateRouteRequest $request, Route $route)
    {
        $this->ensureRouteAccess($route);
        $this->routeService->update($route, $request->validated());

        return redirect()->route('routes.show', $route)
            ->with('success', 'Ruta actualizada exitosamente.');
    }

    public function toggle(Route $route)
    {
        $this->ensureRouteAccess($route);
        $route = $this->routeService->toggleActive($route);
        $status = $route->active ? 'activada' : 'desactivada';

        return redirect()->back()
            ->with('success', "Ruta {$status} exitosamente.");
    }

    public function storeCollection(StoreRouteCollectionRequest $request, Route $route)
    {
        $this->ensureRouteAccess($route);
        $this->collectionService->recordForRoute($route, $request);

        return redirect()->back()
            ->with('success', 'Acopio del día registrado.');
    }

    public function assignRutero(AssignRuteroToRouteRequest $request, Route $route)
    {
        $this->ensureRouteAccess($route);
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
        $this->ensureRouteAccess($route);
        Rutero::query()
            ->where('route_id', $route->id)
            ->update(['route_id' => null]);

        return redirect()->route('routes.show', $route)
            ->with('success', 'Rutero desasignado de la ruta.');
    }

    public function moveProducers(MoveRouteProducersRequest $request, Route $route)
    {
        $this->ensureRouteAccess($route);
        $target = Route::query()->findOrFail($request->integer('target_route_id'));
        $this->ensureRouteAccess($target);

        try {
            $moved = $this->routeService->moveProducers(
                $route,
                $target,
                $request->validated('producer_ids'),
                $request->user(),
                $request->validated('reason'),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "{$moved} productores trasladados a {$target->name}.");
    }

    public function split(SplitRouteRequest $request, Route $route)
    {
        $this->ensureRouteAccess($route);

        try {
            $newRoute = $this->routeService->split(
                $route,
                $request->validated('name'),
                $request->validated('producer_ids'),
                $request->user(),
                $request->validated('reason'),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('routes.show', $newRoute)
            ->with('success', 'Ruta separada correctamente. Ahora puede asignarle un rutero.');
    }

    public function merge(MergeRoutesRequest $request, Route $route)
    {
        $this->ensureRouteAccess($route);
        $target = Route::query()->findOrFail($request->integer('target_route_id'));
        $this->ensureRouteAccess($target);

        try {
            $moved = $this->routeService->merge($route, $target, $request->user(), $request->validated('reason'));
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('routes.show', $target)
            ->with('success', "Rutas unidas. {$moved} productores quedaron activos en {$target->name}.");
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

    private function ensureRouteAccess(Route $route): void
    {
        abort_unless($this->routeService->userCanAccess(request()->user(), $route), 404);
    }
}
