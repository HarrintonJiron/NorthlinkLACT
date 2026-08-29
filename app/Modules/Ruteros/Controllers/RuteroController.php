<?php

namespace App\Modules\Ruteros\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\Route;
use App\Modules\Ruteros\Models\Rutero;
use App\Modules\Ruteros\Requests\StoreRuteroRequest;
use App\Modules\Ruteros\Requests\UpdateRuteroRequest;
use Inertia\Inertia;

class RuteroController extends Controller
{
    public function index()
    {
        return Inertia::render('Ruteros/Index', [
            'ruteros' => Rutero::query()
                ->with('route:id,code,name')
                ->latest()
                ->get(),
            'availableRoutes' => $this->availableRoutes(),
            'stats' => [
                'total' => Rutero::query()->count(),
                'active' => Rutero::query()->where('active', true)->count(),
                'inactive' => Rutero::query()->where('active', false)->count(),
            ],
        ]);
    }

    public function store(StoreRuteroRequest $request)
    {
        Rutero::query()->create([
            ...$request->validated(),
            'active' => true,
        ]);

        return redirect()->route('ruteros.index')
            ->with('success', 'Rutero registrado exitosamente.');
    }

    public function show(Rutero $rutero)
    {
        $rutero->load('route:id,code,name,active');

        return Inertia::render('Ruteros/Show', [
            'rutero' => $rutero,
            'availableRoutes' => $this->availableRoutes($rutero),
        ]);
    }

    public function update(UpdateRuteroRequest $request, Rutero $rutero)
    {
        $rutero->update($request->validated());

        return redirect()->route('ruteros.show', $rutero)
            ->with('success', 'Rutero actualizado exitosamente.');
    }

    public function toggle(Rutero $rutero)
    {
        $rutero->update(['active' => ! $rutero->active]);
        $status = $rutero->active ? 'activado' : 'desactivado';

        return redirect()->back()
            ->with('success', "Rutero {$status} exitosamente.");
    }

    protected function availableRoutes(?Rutero $rutero = null)
    {
        return Route::query()
            ->where('active', true)
            ->where(function ($query) use ($rutero) {
                $query->whereDoesntHave('rutero');

                if ($rutero) {
                    $query->orWhere('id', $rutero->route_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }
}
