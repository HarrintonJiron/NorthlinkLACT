<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Models\FinanceCategory;
use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Finanzas\Requests\StoreFinanceTransactionRequest;
use App\Modules\Finanzas\Requests\UpdateFinanceTransactionRequest;
use App\Modules\Finanzas\Requests\UpdateFinanceTransactionStatusRequest;
use App\Modules\Finanzas\Services\FinanzasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceTransactionController extends Controller
{
    public function __construct(
        private FinanzasService $finanzasService
    ) {}

    public function index(Request $request)
    {
        return $this->renderIndex(null, $request);
    }

    public function payments(Request $request)
    {
        return $this->renderIndex(FinanceTransaction::TYPE_PAGO, $request);
    }

    private function renderIndex(?string $initialType = null, ?Request $request = null)
    {
        $this->finanzasService->ensureDefaultCategories();
        $transactions = FinanceTransaction::query()
            ->with('category:id,code,name,type')
            ->when($request?->string('search')->trim()->toString(), fn ($query, $search) => $query->where(fn ($inner) => $inner->where('concept', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")))
            ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(30)->withQueryString();

        return Inertia::render('Finanzas/Index', [
            'transactions' => $transactions->items(),
            'pagination' => ['links' => $transactions->linkCollection(), 'from' => $transactions->firstItem(), 'to' => $transactions->lastItem(), 'total' => $transactions->total()],
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
            'initialType' => $initialType,
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
        $this->finanzasService->updateTransaction($transaction, $request->safe()->except(['code']));

        return redirect()->route('finanzas.index')
            ->with('success', 'Movimiento actualizado exitosamente.');
    }

    public function toggle(FinanceTransaction $transaction)
    {
        return redirect()->back()->withErrors([
            'transaction' => 'Usa el flujo de estados y registra el motivo para anular un movimiento.',
        ]);
    }

    public function updateStatus(UpdateFinanceTransactionStatusRequest $request, FinanceTransaction $transaction)
    {
        $this->finanzasService->transition(
            $transaction,
            $request->validated('status'),
            $request->validated('reason')
        );

        return redirect()->back()->with('success', 'Estado financiero actualizado correctamente.');
    }

    public function destroy(FinanceTransaction $transaction)
    {
        return redirect()->route('finanzas.index')->withErrors([
            'transaction' => 'Los movimientos financieros no se eliminan. Debes anularlos con una justificación.',
        ]);
    }
}
