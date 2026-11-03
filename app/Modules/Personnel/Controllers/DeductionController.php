<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Requests\StoreDeductionRequest;
use Illuminate\Http\RedirectResponse;

class DeductionController extends Controller
{
    public function store(StoreDeductionRequest $request, Employee $employee): RedirectResponse
    {
        $employee->deductions()->create([
            ...$request->validated(),
            'status' => EmployeeDeduction::STATUS_PENDING,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Deducción registrada. Se aplicará en la próxima planilla del colaborador.');
    }

    public function update(StoreDeductionRequest $request, Employee $employee, EmployeeDeduction $deduction): RedirectResponse
    {
        abort_if($deduction->employee_id !== $employee->id, 404);
        abort_if($deduction->status !== EmployeeDeduction::STATUS_PENDING, 422, 'Esta deducción ya fue aplicada a una planilla y no se puede editar.');

        $deduction->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Deducción actualizada.');
    }

    public function destroy(Employee $employee, EmployeeDeduction $deduction): RedirectResponse
    {
        abort_if($deduction->employee_id !== $employee->id, 404);
        abort_if($deduction->status !== EmployeeDeduction::STATUS_PENDING, 422, 'Esta deducción ya fue aplicada a una planilla.');

        $deduction->delete();

        return redirect()
            ->back()
            ->with('success', 'Deducción eliminada.');
    }
}
