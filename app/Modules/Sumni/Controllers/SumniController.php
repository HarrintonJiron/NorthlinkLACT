<?php

namespace App\Modules\Sumni\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Services\MilkCollectionService;
use App\Modules\Sumni\Requests\StoreSumniCollectionRequest;
use Inertia\Inertia;

class SumniController extends Controller
{
    public function __construct(
        private MilkCollectionService $collectionService
    ) {}

    public function index()
    {
        $today = now()->toDateString();

        $todayByRoute = MilkCollection::query()
            ->whereDate('collection_date', $today)
            ->get(['route_id', 'liters'])
            ->groupBy('route_id');

        $routes = Route::query()
            ->where('active', true)
            ->with(['activeAssignments.producer', 'rutero'])
            ->orderBy('name')
            ->get()
            ->map(function (Route $route) use ($todayByRoute) {
                $clients = $route->activeAssignments
                    ->pluck('producer')
                    ->filter(fn ($producer) => $producer && $producer->active)
                    ->count();

                $collections = $todayByRoute->get($route->id, collect());

                return [
                    'id' => $route->id,
                    'code' => $route->code,
                    'name' => $route->name,
                    'owner_name' => $route->rutero?->full_name,
                    'owner_phone' => $route->rutero?->phone,
                    'vehicle_plate' => $route->rutero?->vehicle_plate,
                    'clients' => $clients,
                    'today_visits' => $collections->count(),
                    'today_liters' => round((float) $collections->sum('liters'), 2),
                ];
            })
            ->values();

        return Inertia::render('Sumni/Index', [
            'today' => $today,
            'routes' => $routes,
        ]);
    }

    public function show(Route $route)
    {
        if (! $route->active) {
            abort(404);
        }

        $route->load('rutero');

        $today = now()->toDateString();

        $todayLiters = MilkCollection::query()
            ->where('route_id', $route->id)
            ->whereDate('collection_date', $today)
            ->get(['producer_id', 'liters'])
            ->keyBy('producer_id');

        $clients = Producer::query()
            ->where('active', true)
            ->whereHas('activeAssignment', fn ($query) => $query->where('route_id', $route->id))
            ->orderBy('full_name')
            ->get()
            ->map(function (Producer $producer) use ($todayLiters) {
                $collection = $todayLiters->get($producer->id);

                return [
                    'id' => $producer->id,
                    'full_name' => $producer->full_name,
                    'identity_number' => $producer->identity_number,
                    'phone' => $producer->phone,
                    'code' => $producer->code,
                    'today_liters' => $collection ? (float) $collection->liters : null,
                ];
            })
            ->values();

        return Inertia::render('Sumni/Show', [
            'today' => $today,
            'route' => [
                'id' => $route->id,
                'code' => $route->code,
                'name' => $route->name,
                'owner_name' => $route->rutero?->full_name,
                'owner_phone' => $route->rutero?->phone,
                'vehicle_plate' => $route->rutero?->vehicle_plate,
            ],
            'clients' => $clients,
        ]);
    }

    public function store(StoreSumniCollectionRequest $request, Route $route)
    {
        if (! $route->active) {
            abort(404);
        }

        $producer = Producer::query()
            ->with('activeAssignment.route')
            ->findOrFail($request->validated('producer_id'));

        $assignedRoute = $producer->activeAssignment?->route;

        if (! $assignedRoute || (int) $assignedRoute->id !== (int) $route->id) {
            return back()->with('error', 'Este cliente no pertenece a esta ruta.');
        }

        $this->collectionService->record(
            $route,
            $producer->id,
            now()->toDateString(),
            (float) $request->validated('liters'),
            $request->user()
        );

        return redirect()->route('sumni.show', $route)
            ->with('success', "Se registraron {$request->validated('liters')} L de {$producer->full_name}.");
    }
}
