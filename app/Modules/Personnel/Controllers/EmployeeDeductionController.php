<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Requests\StoreEmployeeDeductionRequest;
use Illuminate\Http\RedirectResponse;

class EmployeeDeductionController extends Controller
{
    public function store(StoreEmployeeDeductionRequest $request, ?Employee $employee = null): RedirectResponse
    {
        $data = $request->validated();
        $targetEmployeeId = $employee?->id ?? $data['employee_id'];

        EmployeeDeduction::query()->create([
            'employee_id' => $targetEmployeeId,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'total_amount' => $data['total_amount'] ?? null,
            'installment_amount' => $data['installment_amount'] ?? null,
            'installments_total' => $data['installments_total'] ?? null,
            'installments_paid' => 0,
            'deduction_date' => $data['deduction_date'],
            'reason' => $data['reason'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('employees.show', $targetEmployeeId)
            ->with('success', 'Deducción registrada correctamente.');
    }

    public function destroy(Employee $employee, EmployeeDeduction $deduction): RedirectResponse
    {
        abort_unless($deduction->employee_id === $employee->id, 404);

        $deduction->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Deducción eliminada correctamente.');
    }
}
