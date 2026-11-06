<?php

namespace App\Modules\Offline\Services;

use App\Exceptions\OfflineSyncConflictException;
use App\Models\Device;
use App\Models\RouteOperatorAssignment;
use App\Models\RouteRun;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use Illuminate\Support\Facades\DB;

class OfflineSyncService
{
    /**
     * @param  array{
     *     device_uuid: string,
     *     route_run: array{
     *         uuid: string,
     *         route_id: int,
     *         run_date: string,
     *         status: string,
     *         started_at: string,
     *         completed_at?: string|null
     *     },
     *     collections: array<int, array{
     *         uuid: string,
     *         producer_id: int,
     *         collection_date: string,
     *         liters: float|int|string,
     *         density?: float|int|string|null,
     *         original_printed_at?: string|null,
     *         print_copy_count: int,
     *         temperature?: float|int|string|null,
     *         acidity?: float|int|string|null,
     *         fat_percentage?: float|int|string|null,
     *         notes?: string|null
     *     }>
     * } $payload
     * @return array<string, mixed>
     */
    public function sync(User $user, array $payload): array
    {
        try {
            return DB::transaction(function () use ($user, $payload): array {
                $device = Device::query()
                    ->where('device_uuid', $payload['device_uuid'])
                    ->lockForUpdate()
                    ->first();

                if (! $device || ! $device->active || $device->user_id !== $user->id) {
                    return $this->conflict('El dispositivo no está activo o no pertenece al usuario.');
                }

                $runPayload = $payload['route_run'];
                $route = Route::query()
                    ->whereKey($runPayload['route_id'])
                    ->where('active', true)
                    ->first();

                if (! $route) {
                    return $this->conflict('La ruta no existe o está inactiva.');
                }

                if (! $this->userCanAccessRoute($user, $route)) {
                    return $this->conflict('El usuario no tiene acceso a la compañía o planta de la ruta.');
                }

                if (! $this->userIsAssignedToRoute($user, $route, $runPayload['run_date'])) {
                    return $this->conflict('La ruta no está asignada al usuario para esta jornada.');
                }

                $routeRun = RouteRun::query()
                    ->where('external_uuid', $runPayload['uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($routeRun && (
                    $routeRun->route_id !== $route->id
                    || $routeRun->user_id !== $user->id
                    || $routeRun->device_id !== $device->id
                    || $routeRun->run_date->toDateString() !== $runPayload['run_date']
                )) {
                    return $this->conflict('El UUID de la ejecución ya representa otra ruta.');
                }

                $registeredProducers = $this->registerFieldProducers(
                    $payload['new_producers'] ?? [],
                    $route,
                    $user,
                    $runPayload['run_date'],
                );
                $collections = $this->resolveProducerIds($payload['collections'], $registeredProducers);

                $collectionConflicts = $this->collectionConflicts($collections, $route, $device);

                if ($collectionConflicts !== []) {
                    throw new OfflineSyncConflictException([
                        'sync_status' => RouteRun::SYNC_CONFLICT,
                        'message' => 'El lote contiene acopios que entran en conflicto con datos sincronizados.',
                        'conflicts' => $collectionConflicts,
                    ]);
                }

                $routeRun = RouteRun::query()->updateOrCreate(
                    ['external_uuid' => $runPayload['uuid']],
                    [
                        'route_id' => $route->id,
                        'user_id' => $user->id,
                        'device_id' => $device->id,
                        'run_date' => $runPayload['run_date'],
                        'status' => $runPayload['status'],
                        'sync_status' => RouteRun::SYNC_SYNCED,
                        'started_at' => $runPayload['started_at'],
                        'completed_at' => $runPayload['completed_at'] ?? null,
                        'synced_at' => now(),
                    ],
                );

                $created = 0;
                $alreadySynced = 0;
                $correctionRequestsRecorded = 0;

                foreach ($collections as $collectionPayload) {
                    $existing = MilkCollection::query()
                        ->where('external_uuid', $collectionPayload['uuid'])
                        ->first();

                    if ($existing) {
                        $existing->update([
                            'original_printed_at' => $existing->original_printed_at ?? ($collectionPayload['original_printed_at'] ?? null),
                            'print_copy_count' => max($existing->print_copy_count, $collectionPayload['print_copy_count'] ?? 0),
                            'synced_at' => now(),
                        ]);
                        if ($this->recordCorrectionRequest($existing, $user, $collectionPayload['correction_reason'] ?? null)) {
                            $correctionRequestsRecorded++;
                        }
                        $alreadySynced++;

                        continue;
                    }

                    $producer = Producer::query()->findOrFail($collectionPayload['producer_id']);
                    $collection = MilkCollection::query()->create([
                        'company_id' => $route->company_id,
                        'plant_id' => $route->plant_id,
                        'route_id' => $route->id,
                        'producer_id' => $producer->id,
                        'collection_date' => $collectionPayload['collection_date'],
                        'liters' => $collectionPayload['liters'],
                        'visit_status' => $collectionPayload['visit_status'] ?? 'entregado',
                        'density' => $collectionPayload['density'] ?? null,
                        'original_printed_at' => $collectionPayload['original_printed_at'] ?? null,
                        'print_copy_count' => $collectionPayload['print_copy_count'] ?? 0,
                        'temperature' => $collectionPayload['temperature'] ?? null,
                        'acidity' => $collectionPayload['acidity'] ?? null,
                        'fat_percentage' => $collectionPayload['fat_percentage'] ?? null,
                        'collected_by' => $user->id,
                        'notes' => $collectionPayload['notes'] ?? null,
                        'external_uuid' => $collectionPayload['uuid'],
                        'route_run_id' => $routeRun->id,
                        'device_id' => $device->id,
                        'sync_status' => RouteRun::SYNC_SYNCED,
                        'synced_at' => now(),
                    ]);

                    $this->auditCollection($collection, $user);
                    if ($this->recordCorrectionRequest($collection, $user, $collectionPayload['correction_reason'] ?? null)) {
                        $correctionRequestsRecorded++;
                    }
                    $created++;
                }

                $device->update(['last_seen_at' => now()]);

                return [
                    'sync_status' => RouteRun::SYNC_SYNCED,
                    'route_run_uuid' => $routeRun->external_uuid,
                    'created' => $created,
                    'already_synced' => $alreadySynced,
                    'correction_requests_recorded' => $correctionRequestsRecorded,
                    'conflicts' => [],
                    'registered_producers' => collect($registeredProducers)->map(fn (int $id, string $uuid): array => [
                        'external_uuid' => $uuid,
                        'producer_id' => $id,
                    ])->values()->all(),
                ];
            });
        } catch (OfflineSyncConflictException $exception) {
            return $exception->result;
        }
    }

    /** @return array<string, int> */
    private function registerFieldProducers(array $newProducers, Route $route, User $user, string $date): array
    {
        $registered = [];

        foreach ($newProducers as $payload) {
            $producer = Producer::query()->firstOrCreate(
                ['external_uuid' => $payload['external_uuid']],
                [
                    'code' => 'CAMPO-'.strtoupper(substr(str_replace('-', '', $payload['external_uuid']), 0, 12)),
                    'full_name' => $payload['full_name'],
                    'phone' => $payload['phone'] ?? null,
                    'active' => true,
                ],
            );

            $alreadyAssigned = ProducerRouteAssignment::query()
                ->where('producer_id', $producer->id)
                ->where('route_id', $route->id)
                ->whereDate('assigned_at', $date)
                ->exists();

            if (! $alreadyAssigned) {
                ProducerRouteAssignment::query()->create([
                    'producer_id' => $producer->id,
                    'route_id' => $route->id,
                    'assigned_at' => $date,
                    'assigned_by' => $user->id,
                ]);
            }

            $registered[$payload['external_uuid']] = $producer->id;
        }

        return $registered;
    }

    private function resolveProducerIds(array $collections, array $registeredProducers): array
    {
        return collect($collections)->map(function (array $collection) use ($registeredProducers): array {
            if (isset($collection['producer_external_uuid'])) {
                $collection['producer_id'] = $registeredProducers[$collection['producer_external_uuid']] ?? null;
                unset($collection['producer_external_uuid']);
            }

            return $collection;
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $collections
     * @return array<int, array{uuid: string, message: string}>
     */
    private function collectionConflicts(array $collections, Route $route, Device $device): array
    {
        $conflicts = [];

        foreach ($collections as $collection) {
            $producerIsAssigned = Producer::query()
                ->whereKey($collection['producer_id'])
                ->where('active', true)
                ->whereHas('assignments', fn ($query) => $query
                    ->where('route_id', $route->id)
                    ->whereDate('assigned_at', '<=', $collection['collection_date'])
                    ->where(fn ($assignment) => $assignment
                        ->whereNull('ended_at')
                        ->orWhereDate('ended_at', '>=', $collection['collection_date'])))
                ->exists();

            if (! $producerIsAssigned) {
                $conflicts[] = [
                    'uuid' => $collection['uuid'],
                    'message' => 'El productor no está activo o asignado a la ruta para esa fecha.',
                ];

                continue;
            }

            $existingByUuid = MilkCollection::query()
                ->where('external_uuid', $collection['uuid'])
                ->first();

            if ($existingByUuid) {
                if (! $this->sameCollection($existingByUuid, $collection, $route, $device)) {
                    $conflicts[] = [
                        'uuid' => $collection['uuid'],
                        'message' => 'El UUID ya representa un acopio con datos diferentes.',
                    ];
                }

                continue;
            }

            $businessDuplicate = MilkCollection::query()
                ->where('company_id', $route->company_id)
                ->where('plant_id', $route->plant_id)
                ->where('route_id', $route->id)
                ->where('producer_id', $collection['producer_id'])
                ->whereDate('collection_date', $collection['collection_date'])
                ->exists();

            if ($businessDuplicate) {
                $conflicts[] = [
                    'uuid' => $collection['uuid'],
                    'message' => 'Ya existe un acopio para el productor, ruta y fecha.',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sameCollection(
        MilkCollection $existing,
        array $payload,
        Route $route,
        Device $device,
    ): bool {
        return $existing->route_id === $route->id
            && $existing->device_id === $device->id
            && $existing->producer_id === (int) $payload['producer_id']
            && $existing->collection_date->toDateString() === $payload['collection_date']
            && $existing->liters === number_format((float) $payload['liters'], 2, '.', '')
            && $existing->visit_status === ($payload['visit_status'] ?? 'entregado')
            && $existing->density === $this->decimal($payload['density'] ?? null, 3)
            && $existing->temperature === $this->decimal($payload['temperature'] ?? null)
            && $existing->acidity === $this->decimal($payload['acidity'] ?? null)
            && $existing->fat_percentage === $this->decimal($payload['fat_percentage'] ?? null);
    }

    private function decimal(mixed $value, int $precision = 2): ?string
    {
        return $value === null ? null : number_format((float) $value, $precision, '.', '');
    }

    private function userCanAccessRoute(User $user, Route $route): bool
    {
        return DB::table('company_user')
            ->where('user_id', $user->id)
            ->where('company_id', $route->company_id)
            ->where(fn ($query) => $query
                ->whereNull('plant_id')
                ->orWhere('plant_id', $route->plant_id))
            ->exists();
    }

    private function userIsAssignedToRoute(User $user, Route $route, string $runDate): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return RouteOperatorAssignment::query()
            ->where('route_id', $route->id)
            ->where('user_id', $user->id)
            ->whereDate('assigned_date', $runDate)
            ->exists();
    }

    /**
     * @return array{sync_status: string, message: string, conflicts: array<int, mixed>}
     */
    private function conflict(string $message): array
    {
        return [
            'sync_status' => RouteRun::SYNC_CONFLICT,
            'message' => $message,
            'conflicts' => [],
        ];
    }

    private function auditCollection(MilkCollection $collection, User $user): void
    {
        AuditEvent::query()->create([
            'user_id' => $user->id,
            'company_id' => $collection->company_id,
            'plant_id' => $collection->plant_id,
            'entity_type' => MilkCollection::class,
            'entity_id' => $collection->id,
            'action' => 'sync',
            'description' => 'Acopio sincronizado desde dispositivo offline',
            'new_values' => $collection->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function recordCorrectionRequest(MilkCollection $collection, User $user, ?string $reason): bool
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            return false;
        }

        if (
            $collection->correction_status === MilkCollection::CORRECTION_REQUESTED
            && $collection->correction_reason === $reason
        ) {
            return false;
        }

        $collection->update([
            'correction_status' => MilkCollection::CORRECTION_REQUESTED,
            'correction_reason' => $reason,
            'correction_requested_at' => now(),
            'correction_requested_by' => $user->id,
        ]);

        AuditEvent::query()->create([
            'user_id' => $user->id,
            'company_id' => $collection->company_id,
            'plant_id' => $collection->plant_id,
            'entity_type' => MilkCollection::class,
            'entity_id' => $collection->id,
            'action' => 'correction_requested',
            'description' => 'Rutero solicitó revisión o corrección de un acopio',
            'new_values' => [
                'status' => MilkCollection::CORRECTION_REQUESTED,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return true;
    }
}
