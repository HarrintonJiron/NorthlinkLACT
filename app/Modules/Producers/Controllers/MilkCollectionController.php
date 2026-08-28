<?php

namespace App\Modules\Producers\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Requests\StoreMilkCollectionRequest;
use App\Modules\Producers\Services\MilkCollectionService;
use Inertia\Inertia;

class MilkCollectionController extends Controller
{
    public function __construct(
        private MilkCollectionService $collectionService
    ) {}

    public function index()
    {
        // Temporalmente sin filtro por usuario durante desarrollo
        $collections = MilkCollection::with(['producer', 'route', 'company', 'plant'])
            ->orderBy('collection_date', 'desc')
            ->get();

        return Inertia::render('Producers/Collections/Index', [
            'collections' => $collections,
        ]);
    }

    public function create()
    {
        return Inertia::render('Producers/Collections/Create');
    }

    public function store(StoreMilkCollectionRequest $request)
    {
        try {
            $collection = $this->collectionService->createWithLock($request);

            return redirect()->route('collections.index')
                ->with('success', 'Acopio registrado exitosamente.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show(MilkCollection $collection)
    {
        // Temporalmente sin authorize durante desarrollo
        // $this->authorize('view', $collection);

        $collection->load(['producer', 'route', 'company', 'plant', 'collectedBy', 'verifiedBy']);

        return Inertia::render('Producers/Collections/Show', [
            'collection' => $collection,
        ]);
    }
}
