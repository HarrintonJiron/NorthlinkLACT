<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Requests\StoreProducerRequest;
use App\Modules\Producers\Requests\UpdateProducerRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProducerController extends Controller
{
    public function index()
    {
        $producers = Producer::with('activeAssignment.route')
            ->orderBy('full_name')
            ->paginate(20);

        return Inertia::render('Producers/Producers/Index', [
            'producers' => $producers,
            'routes' => \App\Modules\Producers\Models\Route::where('active', true)->get(),
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
        $producer = Producer::create($request->validated());

        if ($request->has('route_id') && $request->route_id) {
            ProducerRouteAssignment::create([
                'producer_id' => $producer->id,
                'route_id' => $request->route_id,
                'payment_method' => $request->payment_method ?? 'cash',
                'started_at' => now(),
            ]);
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
        $producer->update($request->validated());

        if ($request->has('route_id') && $request->route_id) {
            $assignment = $producer->activeAssignment;
            
            if ($assignment) {
                if ($assignment->route_id != $request->route_id || $assignment->payment_method != $request->payment_method) {
                    $assignment->update([
                        'ended_at' => now(),
                    ]);
                    
                    ProducerRouteAssignment::create([
                        'producer_id' => $producer->id,
                        'route_id' => $request->route_id,
                        'payment_method' => $request->payment_method ?? 'cash',
                        'started_at' => now(),
                    ]);
                }
            } else {
                ProducerRouteAssignment::create([
                    'producer_id' => $producer->id,
                    'route_id' => $request->route_id,
                    'payment_method' => $request->payment_method ?? 'cash',
                    'started_at' => now(),
                ]);
            }
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
}
