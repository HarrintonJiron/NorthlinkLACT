<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Models\FinanceCategory;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Finanzas\Requests\StoreFinanceTransactionRequest;
use App\Modules\Finanzas\Requests\UpdateFinanceTransactionRequest;
use App\Modules\Finanzas\Services\FinanzasService;
use Inertia\Inertia;

class FinanceTransactionController extends Controller
{
    public function __construct(
        private FinanzasService $finanzasService
    ) {}

    public function index()
    {
        $this->finanzasService->ensureDefaultCategories();

        return Inertia::render('Finanzas/Index', [
            'transactions' => FinanceTransaction::query()
                ->with('category:id,code,name,type')
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->get(),
            'categories' => FinanceCategory::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'type']),
            'stats' => $this->finanzasService->stats(),
            'typeOptions' => [
                ['value' => FinanceTransaction::TYPE_GASTO, 'label' => 'Gasto'],
                ['value' => FinanceTransaction::TYPE_PAGO, 'label' => 'Pago'],
                ['value' => FinanceTransaction::TYPE_INGRESO, 'label' => 'Ingreso'],
            ],
            'paymentMethods' => [
                ['value' => 'efectivo', 'label' => 'Efectivo'],
                ['value' => 'transferencia', 'label' => 'Transferencia'],
                ['value' => 'cheque', 'label' => 'Cheque'],
                ['value' => 'tarjeta', 'label' => 'Tarjeta'],
                ['value' => 'otro', 'label' => 'Otro'],
            ],
        ]);
    }

    public function store(StoreFinanceTransactionRequest $request)
    {
        $this->finanzasService->createTransaction($request->safe()->except(['code']));

        return redirect()->route('finanzas.index')
            ->with('success', 'Movimiento registrado exitosamente.');
    }

    public function update(UpdateFinanceTransactionRequest $request, FinanceTransaction $transaction)
    {
        $data = $request->safe()->except(['code']);
        $data['amount'] = (float) $data['amount'];
        if (array_key_exists('active', $data)) {
            $data['active'] = (bool) $data['active'];
        }

        $transaction->update($data);

        return redirect()->route('finanzas.index')
            ->with('success', 'Movimiento actualizado exitosamente.');
    }

    public function toggle(FinanceTransaction $transaction)
    {
        $transaction->update(['active' => ! $transaction->active]);
        $status = $transaction->active ? 'activado' : 'anulado';

        return redirect()->back()
            ->with('success', "Movimiento {$status} exitosamente.");
    }

    public function destroy(FinanceTransaction $transaction)
    {
        $transaction->delete();

        return redirect()->route('finanzas.index')
            ->with('success', 'Movimiento eliminado exitosamente.');
    }
}
