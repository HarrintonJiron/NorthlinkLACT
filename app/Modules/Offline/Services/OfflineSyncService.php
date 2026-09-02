<?php

namespace App\Modules\Offline\Services;

use App\Models\Device;
use App\Models\RouteRun;
use App\Models\User;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
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

            $collectionConflicts = $this->collectionConflicts(
                $payload['collections'],
                $route,
                $device,
            );

            if ($collectionConflicts !== []) {
                return [
                    'sync_status' => RouteRun::SYNC_CONFLICT,
                    'message' => 'El lote contiene acopios que entran en conflicto con datos sincronizados.',
                    'conflicts' => $collectionConflicts,
                ];
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

            foreach ($payload['collections'] as $collectionPayload) {
                $existing = MilkCollection::query()
                    ->where('external_uuid', $collectionPayload['uuid'])
                    ->first();

                if ($existing) {
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
                $created++;
            }

            $device->update(['last_seen_at' => now()]);

            return [
                'sync_status' => RouteRun::SYNC_SYNCED,
                'route_run_uuid' => $routeRun->external_uuid,
                'created' => $created,
                'already_synced' => $alreadySynced,
                'conflicts' => [],
            ];
        });
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
            && $existing->temperature === $this->decimal($payload['temperature'] ?? null)
            && $existing->acidity === $this->decimal($payload['acidity'] ?? null)
            && $existing->fat_percentage === $this->decimal($payload['fat_percentage'] ?? null);
    }

    private function decimal(mixed $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
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
}
