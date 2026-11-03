<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeLoan;
use App\Modules\Personnel\Requests\StoreLoanRequest;
use Illuminate\Http\RedirectResponse;

class LoanController extends Controller
{
    public function store(StoreLoanRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        $employee->loans()->create([
            ...$data,
            'remaining_balance' => $data['amount'],
            'status' => EmployeeLoan::STATUS_ACTIVE,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Préstamo registrado. Se descontará por planilla hasta saldarse.');
    }

    public function update(StoreLoanRequest $request, Employee $employee, EmployeeLoan $loan): RedirectResponse
    {
        abort_if($loan->employee_id !== $employee->id, 404);
        abort_if($loan->remaining_balance != $loan->amount, 422, 'No se puede editar un préstamo con cuotas ya descontadas.');

        $data = $request->validated();

        $loan->update([
            ...$data,
            'remaining_balance' => $data['amount'],
        ]);

        return redirect()
            ->back()
            ->with('success', 'Préstamo actualizado.');
    }

    public function destroy(Employee $employee, EmployeeLoan $loan): RedirectResponse
    {
        abort_if($loan->employee_id !== $employee->id, 404);
        abort_if($loan->remaining_balance != $loan->amount, 422, 'No se puede eliminar un préstamo con cuotas ya descontadas.');

        $loan->delete();

        return redirect()
            ->back()
            ->with('success', 'Préstamo eliminado.');
    }
}
