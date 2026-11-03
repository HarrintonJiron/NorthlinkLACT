<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeSettlement;
use App\Modules\Personnel\Requests\StoreSettlementRequest;
use App\Modules\Personnel\Services\EmployeeSettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettlementController extends Controller
{
    public function __construct(private readonly EmployeeSettlementService $settlementService) {}

    public function store(StoreSettlementRequest $request): RedirectResponse
    {
        $employee = Employee::findOrFail($request->validated('employee_id'));
        $settlement = $this->settlementService->create($employee, $request->validated());

        return redirect()
            ->route('settlements.show', $settlement)
            ->with('success', 'Liquidación calculada. Revisa el desglose antes de aprobarla.');
    }

    public function show(EmployeeSettlement $settlement): Response
    {
        $settlement->load('employee.role');

        return Inertia::render('Personnel/Payroll/SettlementShow', [
            'settlement' => $settlement,
        ]);
    }

    public function update(StoreSettlementRequest $request, EmployeeSettlement $settlement): RedirectResponse
    {
        abort_if($settlement->status === EmployeeSettlement::STATUS_PAID, 422, 'Una liquidación ya pagada no se puede editar.');
        abort_if((int) $request->validated('employee_id') !== $settlement->employee_id, 422, 'No se puede cambiar el colaborador de una liquidación existente.');

        $wasApproved = $settlement->status === EmployeeSettlement::STATUS_APPROVED;
        $this->settlementService->update($settlement, $request->validated());

        return redirect()
            ->route('settlements.show', $settlement)
            ->with('success', $wasApproved
                ? 'Liquidación corregida; vuelve a aprobarla antes de pagar.'
                : 'Liquidación actualizada.');
    }

    public function approve(EmployeeSettlement $settlement): RedirectResponse
    {
        abort_unless($this->settlementService->approve($settlement), 422, 'Solo se puede aprobar una liquidación en borrador.');

        return redirect()
            ->route('settlements.show', $settlement)
            ->with('success', 'Liquidación aprobada exitosamente.');
    }

    public function markPaid(Request $request, EmployeeSettlement $settlement): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['efectivo', 'transferencia'])],
        ]);

        abort_unless(
            $this->settlementService->markPaid($settlement, $validated['payment_method']),
            422,
            'Solo se puede marcar como pagada una liquidación ya aprobada.'
        );

        return redirect()
            ->route('settlements.show', $settlement)
            ->with('success', 'Liquidación marcada como pagada. El colaborador fue desactivado.');
    }

    public function destroy(EmployeeSettlement $settlement): RedirectResponse
    {
        abort_if($settlement->status !== EmployeeSettlement::STATUS_DRAFT, 422, 'Solo se pueden eliminar liquidaciones en borrador.');

        $settlement->delete();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Liquidación eliminada exitosamente.');
    }
}
