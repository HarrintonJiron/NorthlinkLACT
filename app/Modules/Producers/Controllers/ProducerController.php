<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\ProducerWeekAdjustment;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\CloseProducerPaymentBatchRequest;
use App\Modules\Producers\Requests\ReviseClosedPaymentBatchRequest;
use App\Modules\Producers\Requests\StoreProducerRequest;
use App\Modules\Producers\Requests\StoreProducerWeekAdjustmentRequest;
use App\Modules\Producers\Requests\TransitionProducerPaymentBatchRequest;
use App\Modules\Producers\Requests\UpdateProducerRequest;
use App\Modules\Producers\Requests\UpdateWeeklyMilkPriceRequest;
use App\Modules\Producers\Services\ProducerPaymentService;
use App\Modules\Producers\Services\ProducerService;
use App\Support\InternalReturnPath;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProducerController extends Controller
{
    public function __construct(
        private ProducerService $producerService,
        private ProducerPaymentService $producerPaymentService,
    ) {}

    public function index(Request $request)
    {
        return $this->renderIndex($request);
    }

    public function paymentSheets(Request $request)
    {
        return $this->renderIndex($request, 'payment-sheets');
    }

    private function renderIndex(Request $request, string $mode = 'producers')
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
            'mode' => $mode,
            'paymentBatch' => ProducerPaymentBatch::query()
                ->with('financeTransaction:id,code,status')
                ->whereDate('week_end', $this->producerService->currentPayThursday($week)->toDateString())
                ->first(),
        ]);
    }

    public function closePaymentSheet(CloseProducerPaymentBatchRequest $request)
    {
        $this->producerPaymentService->close($request->validated('week_end'), $request->user(), $request->validated('notes'));

        return back()->with('success', 'Planilla cerrada. Los importes quedaron congelados para auditoría.');
    }

    public function payPaymentSheet(ProducerPaymentBatch $batch)
    {
        $this->producerPaymentService->markPaid($batch, request()->user());

        return back()->with('success', 'Planilla pagada y movimiento financiero generado.');
    }

    public function transitionPaymentSheet(TransitionProducerPaymentBatchRequest $request, ProducerPaymentBatch $batch)
    {
        $updated = $this->producerPaymentService->transition(
            $batch,
            $request->validated('status'),
            $request->user(),
        );

        $message = $updated->status === ProducerPaymentBatch::STATUS_REVIEWED
            ? 'Planilla revisada. Ahora requiere aprobación antes del pago.'
            : 'Planilla aprobada y lista para registrar el pago.';

        return back()->with('success', $message);
    }

    public function updateWeeklyMilkPrice(UpdateWeeklyMilkPriceRequest $request)
    {
        $this->producerPaymentService->setWeeklyPrice(
            $request->validated('week_end'),
            (string) $request->validated('price_per_liter'),
            $request->validated('reason'),
            $request->user(),
        );

        return back()->with('success', 'Precio de la planilla actualizado y registrado en auditoría.');
    }

    public function reviseClosedPaymentPrice(ReviseClosedPaymentBatchRequest $request, ProducerPaymentBatch $batch)
    {
        $this->producerPaymentService->reviseClosedPrice(
            $batch,
            (string) $request->validated('price_per_liter'),
            $request->validated('reason'),
            $request->user(),
        );

        return back()->with('success', 'Precio corregido. La planilla y los tickets fueron recalculados con trazabilidad.');
    }

    public function paymentTicket(ProducerPaymentBatch $batch, Producer $producer)
    {
        $item = $batch->items()
            ->where('producer_id', $producer->id)
            ->firstOrFail();

        $route = $item->route()->with('company')->first();
        $user = request()->user();
        if (! $user->is_admin && (! $route || ! $user->companies()->whereKey($route->company_id)->exists())) {
            abort(404);
        }

        return view('producers.payment-ticket', [
            'batch' => $batch,
            'item' => $item,
            'producer' => $producer,
            'route' => $route,
            'company' => $route?->company,
        ]);
    }

    public function create()
    {
        return Inertia::render('Producers/Producers/Create', [
            'routes' => Route::where('active', true)->get(),
        ]);
    }

    public function store(StoreProducerRequest $request)
    {
        $data = $request->safe()->except(['route_id']);

        if (blank($data['code'] ?? null)) {
            $data['code'] = $this->producerService->nextCode();
        }

        $producer = Producer::create($data);

        $this->assignToRoute($producer, (int) $request->route_id, $request->user()?->id);

        $returnTo = InternalReturnPath::sanitize($request->input('return_to'));

        if ($returnTo) {
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
            'routes' => Route::where('active', true)->get(),
        ]);
    }

    public function update(UpdateProducerRequest $request, Producer $producer)
    {
        $data = $request->safe()->except(['route_id']);

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
        if (ProducerPaymentBatch::query()->whereDate('week_end', $weekEnd)->exists()) {
            return back()->withErrors(['week_end' => 'La semana está cerrada y sus ajustes ya no pueden modificarse.']);
        }
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

        $returnTo = InternalReturnPath::sanitize($request->input('return_to'));
        if ($returnTo) {
            return redirect($returnTo)
                ->with('success', "Ajustes de semana guardados para {$producer->full_name}.");
        }

        return redirect()->route('producers.index', ['week' => $weekEnd])
            ->with('success', "Ajustes de semana guardados para {$producer->full_name}.");
    }

    protected function assignToRoute(Producer $producer, int $routeId, ?int $userId): void
    {
        $assignedBy = $userId ?: User::query()->value('id');

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
