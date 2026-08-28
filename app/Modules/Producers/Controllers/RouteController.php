<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreRouteRequest;
use App\Modules\Producers\Services\RouteService;
use Inertia\Inertia;

class RouteController extends Controller
{
    public function __construct(
        private RouteService $routeService
    ) {}

    public function index()
    {
        // Temporalmente sin filtro por usuario durante desarrollo
        $routes = Route::with(['company', 'plant'])->get();

        return Inertia::render('Producers/Routes/Index', [
            'routes' => $routes,
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

        $route->load(['company', 'plant', 'assignments.producer']);

        return Inertia::render('Producers/Routes/Show', [
            'route' => $route,
        ]);
    }
}
