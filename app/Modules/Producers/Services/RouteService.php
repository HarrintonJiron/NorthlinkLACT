<?php

namespace App\Modules\Producers\Services;

use App\Modules\Producers\Models\Route;
use App\Modules\Producers\Requests\StoreRouteRequest;
use Illuminate\Support\Facades\DB;
use App\Modules\Audit\Models\AuditEvent;

class RouteService
{
    public function create(StoreRouteRequest $request): Route
    {
        return DB::transaction(function () use ($request) {
            $route = Route::create($request->validated());

            $this->auditCreate($route, $request->user());

            return $route;
        });
    }

    public function update(Route $route, array $data): Route
    {
        return DB::transaction(function () use ($route, $data) {
            $oldValues = $route->getAttributes();
            $route->update($data);
            $newValues = $route->getAttributes();

            $this->auditUpdate($route, $oldValues, $newValues, request()->user());

            return $route->fresh();
        });
    }

    protected function auditCreate(Route $route, $user): void
    {
        AuditEvent::create([
            'user_id' => $user->id,
            'company_id' => $route->company_id,
            'plant_id' => $route->plant_id,
            'entity_type' => Route::class,
            'entity_id' => $route->id,
            'action' => 'create',
            'description' => 'Creación de ruta',
            'new_values' => $route->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function auditUpdate(Route $route, array $oldValues, array $newValues, $user): void
    {
        AuditEvent::create([
            'user_id' => $user->id,
            'company_id' => $route->company_id,
            'plant_id' => $route->plant_id,
            'entity_type' => Route::class,
            'entity_id' => $route->id,
            'action' => 'update',
            'description' => 'Actualización de ruta',
            'old_values' => array_diff_assoc($oldValues, $newValues),
            'new_values' => array_diff_assoc($newValues, $oldValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
