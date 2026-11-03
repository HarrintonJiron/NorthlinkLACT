<?php

namespace App\Modules\Production\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Production\Models\FinishedProductLot;
use App\Modules\Production\Models\ProductDispatch;
use App\Modules\Production\Requests\StoreProductDispatchRequest;
use App\Modules\Production\Services\ProductionOutputService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductDispatchController extends Controller
{
    public function __construct(private ProductionOutputService $service) {}

    public function index(): Response
    {
        return Inertia::render('Production/Dispatches', [
            'lots' => FinishedProductLot::query()->where('available_quantity', '>', 0)->with(['product.unit', 'batch:id,batch_number'])->orderBy('expiration_date')->get(),
            'dispatches' => ProductDispatch::query()->with(['items.lot:id,lot_number', 'items.product.unit'])->latest('dispatch_date')->latest('id')->limit(30)->get(),
        ]);
    }

    public function store(StoreProductDispatchRequest $request): RedirectResponse
    {
        $this->service->dispatch($request->validated(), $request->user());

        return back()->with('success', 'Remisión creada y existencias descontadas.');
    }

    public function receive(ProductDispatch $dispatch): RedirectResponse
    {
        $this->service->receive($dispatch, request()->user());

        return back()->with('success', 'Recepción confirmada.');
    }
}
