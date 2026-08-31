<?php

namespace App\Modules\Sumni\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Services\MilkCollectionService;
use App\Modules\Producers\Services\ProducerService;
use App\Modules\Sumni\Requests\StoreSumniCollectionRequest;
use App\Modules\Sumni\Requests\StoreSumniProducerRequest;
use Inertia\Inertia;

class SumniController extends Controller
{
    public function __construct(
        private MilkCollectionService $collectionService,
        private ProducerService $producerService,
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
                    'owner_name' => $route->rutero?->owner_name,
                    'owner_phone' => $route->rutero?->owner_phone,
                    'driver_name' => $route->rutero?->driver_name,
                    'driver_phone' => $route->rutero?->driver_phone,
                    'vehicle_description' => $route->rutero?->vehicle_description,
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
            ->get(['producer_id', 'liters', 'temperature'])
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
                    'today_density' => $collection && $collection->temperature !== null ? (float) $collection->temperature : null,
                    'recorded_today' => $collection !== null,
                ];
            })
            ->values();

        $selectProducerId = request()->integer('select') ?: null;
        $lastRecordedProducerId = request()->integer('voucher') ?: null;

        return Inertia::render('Sumni/Show', [
            'today' => $today,
            'route' => [
                'id' => $route->id,
                'code' => $route->code,
                'name' => $route->name,
                'owner_name' => $route->rutero?->owner_name,
                'owner_phone' => $route->rutero?->owner_phone,
                'driver_name' => $route->rutero?->driver_name,
                'driver_phone' => $route->rutero?->driver_phone,
                'vehicle_description' => $route->rutero?->vehicle_description,
                'vehicle_plate' => $route->rutero?->vehicle_plate,
            ],
            'clients' => $clients,
            'selectProducerId' => $selectProducerId,
            'lastRecordedProducerId' => $lastRecordedProducerId,
        ]);
    }

    public function storeProducer(StoreSumniProducerRequest $request, Route $route)
    {
        if (! $route->active) {
            abort(404);
        }

        $producer = Producer::query()->create([
            'code' => $this->producerService->nextCode(),
            'full_name' => $request->validated('full_name'),
            'identity_number' => $request->validated('identity_number') ?: null,
            'phone' => $request->validated('phone'),
            'active' => true,
        ]);

        $this->assignProducerToRoute($producer, $route, $request->user()?->id);

        return redirect()
            ->route('sumni.show', ['route' => $route, 'select' => $producer->id])
            ->with('success', "{$producer->full_name} quedó registrado en la ruta. Ahora ingresa los litros.");
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

        $today = now()->toDateString();

        $alreadyRecorded = MilkCollection::query()
            ->where('route_id', $route->id)
            ->where('producer_id', $producer->id)
            ->whereDate('collection_date', $today)
            ->exists();

        if ($alreadyRecorded) {
            return back()->with('error', 'Este cliente ya tiene litros registrados hoy. No se puede modificar.');
        }

        try {
            $this->collectionService->record(
                $route,
                $producer->id,
                $today,
                (float) $request->validated('liters'),
                $request->user(),
                immutable: true,
                temperature: (float) $request->validated('temperature'),
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('sumni.show', [
                'route' => $route,
                'voucher' => $producer->id,
            ])
            ->with('success', "Se registraron {$request->validated('liters')} L de {$producer->full_name}.");
    }

    protected function assignProducerToRoute(Producer $producer, Route $route, ?int $userId): void
    {
        $assignedBy = $userId ?: \App\Models\User::query()->value('id');

        if (! $assignedBy) {
            throw new \RuntimeException('No hay un usuario para asignar el productor a la ruta.');
        }

        $existing = ProducerRouteAssignment::query()
            ->where('producer_id', $producer->id)
            ->where('route_id', $route->id)
            ->whereDate('assigned_at', now()->toDateString())
            ->first();

        if ($existing) {
            $existing->update([
                'ended_at' => null,
                'assigned_by' => $assignedBy,
            ]);

            return;
        }

        ProducerRouteAssignment::create([
            'producer_id' => $producer->id,
            'route_id' => $route->id,
            'assigned_at' => now()->toDateString(),
            'assigned_by' => $assignedBy,
        ]);
    }
}
