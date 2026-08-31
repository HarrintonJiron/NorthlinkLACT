<?php

namespace App\Modules\Ruteros\Controllers;

use App\Http\Controllers\Controller;
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

        $returnTo = request()->query('return_to');
        if (! is_string($returnTo) || ! str_starts_with($returnTo, '/')) {
            $returnTo = null;
        }

        return Inertia::render('Ruteros/Show', [
            'rutero' => $rutero,
            'returnTo' => $returnTo,
        ]);
    }

    public function update(UpdateRuteroRequest $request, Rutero $rutero)
    {
        $rutero->update($request->validated());

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && str_starts_with($returnTo, '/')) {
            return redirect($returnTo)
                ->with('success', 'Rutero actualizado exitosamente.');
        }

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
}
