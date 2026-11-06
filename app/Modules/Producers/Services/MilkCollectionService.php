<?php

namespace App\Modules\Producers\Services;

use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreMilkCollectionRequest;
use App\Modules\Producers\Requests\StoreRouteCollectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MilkCollectionService
{
    public function create(StoreMilkCollectionRequest $request): MilkCollection
    {
        return DB::transaction(function () use ($request) {
            $collection = MilkCollection::create([
                ...$request->validated(),
                'collected_by' => $request->user()->id,
            ]);

            $this->auditCreate($collection, $request->user());

            return $collection;
        });
    }

    public function createWithLock(StoreMilkCollectionRequest $request): MilkCollection
    {
        return DB::transaction(function () use ($request) {
            $lock = DB::table('milk_collections')
                ->where('company_id', $request->company_id)
                ->where('plant_id', $request->plant_id)
                ->where('route_id', $request->route_id)
                ->where('producer_id', $request->producer_id)
                ->whereDate('collection_date', $request->collection_date)
                ->lockForUpdate()
                ->first();

            if ($lock) {
                throw new \Exception('Ya existe un registro de acopio para este productor en esta fecha.');
            }

            return $this->create($request);
        });
    }

    public function record(Route $route, int $producerId, string $date, float $liters, $user = null, bool $immutable = false, ?float $temperature = null): MilkCollection
    {
        $date = Carbon::parse($date)->toDateString();
        $collectedBy = $user?->id ?: User::query()->value('id');

        if (! $collectedBy) {
            throw new \RuntimeException('No hay un usuario para registrar el acopio.');
        }

        return DB::transaction(function () use ($route, $producerId, $date, $liters, $collectedBy, $user, $immutable, $temperature) {
            $collection = MilkCollection::query()
                ->where('company_id', $route->company_id)
                ->where('plant_id', $route->plant_id)
                ->where('route_id', $route->id)
                ->where('producer_id', $producerId)
                ->whereDate('collection_date', $date)
                ->lockForUpdate()
                ->first();

            if ($collection) {
                if ($immutable) {
                    throw new \RuntimeException('Este cliente ya tiene litros registrados hoy.');
                }

                $collection->update([
                    'liters' => $liters,
                    'temperature' => $temperature,
                    'collected_by' => $collectedBy,
                ]);
            } else {
                $collection = MilkCollection::query()->create([
                    'company_id' => $route->company_id,
                    'plant_id' => $route->plant_id,
                    'route_id' => $route->id,
                    'producer_id' => $producerId,
                    'collection_date' => $date,
                    'liters' => $liters,
                    'temperature' => $temperature,
                    'collected_by' => $collectedBy,
                ]);
            }

            if ($user) {
                $this->auditCreate($collection, $user);
            }

            return $collection->fresh();
        });
    }

    public function recordForRoute(Route $route, StoreRouteCollectionRequest $request): MilkCollection
    {
        return $this->record(
            $route,
            (int) $request->validated('producer_id'),
            $request->validated('collection_date'),
            (float) $request->validated('liters'),
            $request->user()
        );
    }

    public function resolveCorrection(MilkCollection $collection, array $data, User $user): MilkCollection
    {
        if ($collection->correction_status !== MilkCollection::CORRECTION_REQUESTED) {
            throw new \RuntimeException('Este acopio no tiene una corrección pendiente.');
        }

        $belongsToClosedBatch = ProducerPaymentBatch::query()
            ->whereDate('week_start', '<=', $collection->collection_date)
            ->whereDate('week_end', '>=', $collection->collection_date)
            ->whereHas('items', fn ($query) => $query->where('producer_id', $collection->producer_id))
            ->exists();

        if ($belongsToClosedBatch) {
            throw new \RuntimeException('El acopio pertenece a una planilla cerrada. Debe corregirse mediante un ajuste financiero, no sobrescribiendo el registro.');
        }

        return DB::transaction(function () use ($collection, $data, $user): MilkCollection {
            $locked = MilkCollection::query()->lockForUpdate()->findOrFail($collection->id);
            $oldValues = $locked->only(['liters', 'density', 'notes', 'correction_status']);

            $locked->update([
                'liters' => $data['liters'],
                'density' => $data['density'] ?? null,
                'notes' => $data['notes'] ?? null,
                'correction_status' => MilkCollection::CORRECTION_RESOLVED,
                'correction_resolved_at' => now(),
                'correction_resolved_by' => $user->id,
                'correction_resolution_notes' => $data['resolution_notes'],
                'verified_by' => $user->id,
            ]);

            AuditEvent::query()->create([
                'user_id' => $user->id,
                'company_id' => $locked->company_id,
                'plant_id' => $locked->plant_id,
                'entity_type' => MilkCollection::class,
                'entity_id' => $locked->id,
                'action' => 'correction_resolved',
                'description' => 'Corrección administrativa de acopio solicitada desde campo',
                'old_values' => $oldValues,
                'new_values' => $locked->fresh()->only([
                    'liters',
                    'density',
                    'notes',
                    'correction_status',
                    'correction_resolution_notes',
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $locked->fresh();
        });
    }

    protected function auditCreate(MilkCollection $collection, $user): void
    {
        AuditEvent::create([
            'user_id' => $user?->id,
            'company_id' => $collection->company_id,
            'plant_id' => $collection->plant_id,
            'entity_type' => MilkCollection::class,
            'entity_id' => $collection->id,
            'action' => 'create',
            'description' => 'Registro de acopio de leche',
            'new_values' => $collection->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
