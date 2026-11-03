<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\AguinaldoPeriod;
use App\Modules\Personnel\Requests\StoreAguinaldoPeriodRequest;
use App\Modules\Personnel\Services\AguinaldoService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AguinaldoController extends Controller
{
    public function __construct(private readonly AguinaldoService $aguinaldoService) {}

    public function store(StoreAguinaldoPeriodRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $period = $this->aguinaldoService->generatePeriod((int) $validated['year'], $validated['notes'] ?? null);

        return redirect()
            ->route('aguinaldo.show', $period)
            ->with('success', $period->items->isEmpty()
                ? 'Aguinaldo creado, pero no hay colaboradores elegibles para este periodo.'
                : 'Aguinaldo generado exitosamente.');
    }

    public function show(AguinaldoPeriod $aguinaldoPeriod): Response
    {
        $aguinaldoPeriod->load(['items.employee.role']);

        return Inertia::render('Personnel/Payroll/AguinaldoShow', [
            'period' => $aguinaldoPeriod,
            'totals' => [
                'amount' => (float) $aguinaldoPeriod->items->sum('amount'),
            ],
        ]);
    }

    public function approve(AguinaldoPeriod $aguinaldoPeriod): RedirectResponse
    {
        abort_unless($this->aguinaldoService->approve($aguinaldoPeriod), 422, 'Solo se puede aprobar un aguinaldo en borrador.');

        return redirect()
            ->route('aguinaldo.show', $aguinaldoPeriod)
            ->with('success', 'Aguinaldo aprobado exitosamente.');
    }

    public function markPaid(AguinaldoPeriod $aguinaldoPeriod): RedirectResponse
    {
        abort_unless($this->aguinaldoService->markPaid($aguinaldoPeriod), 422, 'Solo se puede marcar como pagado un aguinaldo ya aprobado.');

        return redirect()
            ->route('aguinaldo.show', $aguinaldoPeriod)
            ->with('success', 'Aguinaldo marcado como pagado.');
    }

    public function destroy(AguinaldoPeriod $aguinaldoPeriod): RedirectResponse
    {
        abort_if($aguinaldoPeriod->status !== AguinaldoPeriod::STATUS_DRAFT, 422, 'Solo se pueden eliminar aguinaldos en borrador.');

        $aguinaldoPeriod->delete();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Aguinaldo eliminado exitosamente.');
    }
}
