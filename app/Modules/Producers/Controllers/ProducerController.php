<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\ProducerWeekAdjustment;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreProducerRequest;
use App\Modules\Producers\Requests\StoreProducerWeekAdjustmentRequest;
use App\Modules\Producers\Requests\UpdateProducerRequest;
use App\Modules\Producers\Services\ProducerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProducerController extends Controller
{
    public function __construct(
        private ProducerService $producerService
    ) {}

    public function index(Request $request)
    {
        $routeId = $request->filled('route_id') ? (int) $request->input('route_id') : null;
        $week = $request->input('week');

        return Inertia::render('Producers/Producers/Index', [
            'stats' => $this->producerService->stats($week),
            'routes' => Route::query()->orderBy('name')->get(['id', 'code', 'name', 'active']),
            'filters' => [
                'route_id' => $routeId,
                'week' => $this->producerService->currentPayThursday($week)->toDateString(),
            ],
            'weeks' => $this->producerService->weekOptions(),
            'report' => $this->producerService->weeklyPayroll($routeId, $week),
        ]);
    }

    public function create()
    {
        return Inertia::render('Producers/Producers/Create', [
            'routes' => \App\Modules\Producers\Models\Route::where('active', true)->get(),
        ]);
    }

    public function store(StoreProducerRequest $request)
    {
        $data = $request->safe()->except(['route_id', 'payment_method']);

        if (blank($data['code'] ?? null)) {
            $data['code'] = $this->producerService->nextCode();
        }

        $producer = Producer::create($data);

        $this->assignToRoute($producer, (int) $request->route_id, $request->user()?->id);

        $returnTo = $request->input('return_to');

        if (is_string($returnTo) && str_starts_with($returnTo, '/')) {
            return redirect($returnTo)
                ->with('success', 'Productor creado exitosamente');
        }

        return redirect()->route('producers.index')
            ->with('success', 'Productor creado exitosamente');
    }

    public function show(Producer $producer)
    {
        $producer->load(['activeAssignment.route', 'collections' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return Inertia::render('Producers/Producers/Show', [
            'producer' => $producer,
        ]);
    }

    public function edit(Producer $producer)
    {
        $producer->load('activeAssignment');

        return Inertia::render('Producers/Producers/Edit', [
            'producer' => $producer,
            'routes' => \App\Modules\Producers\Models\Route::where('active', true)->get(),
        ]);
    }

    public function update(UpdateProducerRequest $request, Producer $producer)
    {
        $data = $request->safe()->except(['route_id', 'payment_method']);

        if (blank($data['code'] ?? null)) {
            unset($data['code']);
        }

        $producer->update($data);

        $assignment = $producer->activeAssignment;
        $routeId = (int) $request->route_id;

        if (! $assignment) {
            $this->assignToRoute($producer, $routeId, $request->user()?->id);
        } elseif ((int) $assignment->route_id !== $routeId) {
            $assignment->update(['ended_at' => now()->toDateString()]);
            $this->assignToRoute($producer, $routeId, $request->user()?->id);
        }

        return redirect()->route('producers.index')
            ->with('success', 'Productor actualizado exitosamente');
    }

    public function destroy(Producer $producer)
    {
        $producer->delete();

        return redirect()->route('producers.index')
            ->with('success', 'Productor eliminado exitosamente');
    }

    public function storeWeekAdjustment(StoreProducerWeekAdjustmentRequest $request, Producer $producer)
    {
        $weekEnd = Carbon::parse($request->validated('week_end'))->toDateString();
        $densityPrice = $request->validated('density_price');
        $advance = (float) ($request->validated('advance_amount') ?? 0);
        $notes = $request->validated('notes');

        if ($densityPrice === null || $densityPrice === '') {
            $densityPrice = null;
        } else {
            $densityPrice = (float) $densityPrice;
        }

        $adjustment = ProducerWeekAdjustment::query()
            ->where('producer_id', $producer->id)
            ->whereDate('week_end', $weekEnd)
            ->first();

        if ($densityPrice === null && $advance <= 0 && blank($notes)) {
            $adjustment?->delete();
        } elseif ($adjustment) {
            $adjustment->update([
                'density_price' => $densityPrice,
                'advance_amount' => $advance,
                'notes' => $notes,
            ]);
        } else {
            ProducerWeekAdjustment::query()->create([
                'producer_id' => $producer->id,
                'week_end' => $weekEnd,
                'density_price' => $densityPrice,
                'advance_amount' => $advance,
                'notes' => $notes,
            ]);
        }

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && str_starts_with($returnTo, '/')) {
            return redirect($returnTo)
                ->with('success', "Ajustes de semana guardados para {$producer->full_name}.");
        }

        return redirect()->route('producers.index', ['week' => $weekEnd])
            ->with('success', "Ajustes de semana guardados para {$producer->full_name}.");
    }

    protected function assignToRoute(Producer $producer, int $routeId, ?int $userId): void
    {
        $assignedBy = $userId ?: \App\Models\User::query()->value('id');

        if (! $assignedBy) {
            throw new \RuntimeException('No hay un usuario para asignar el productor a la ruta.');
        }

        $existing = ProducerRouteAssignment::query()
            ->where('producer_id', $producer->id)
            ->where('route_id', $routeId)
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
            'route_id' => $routeId,
            'assigned_at' => now()->toDateString(),
            'assigned_by' => $assignedBy,
        ]);
    }
}
