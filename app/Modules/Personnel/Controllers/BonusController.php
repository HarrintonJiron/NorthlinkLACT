<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeBonus;
use App\Modules\Personnel\Requests\StoreBonusRequest;
use Illuminate\Http\RedirectResponse;

class BonusController extends Controller
{
    public function store(StoreBonusRequest $request, Employee $employee): RedirectResponse
    {
        $employee->bonuses()->create([
            ...$request->validated(),
            'status' => EmployeeBonus::STATUS_PENDING,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Bono registrado. Se aplicará en la próxima planilla del colaborador.');
    }

    public function update(StoreBonusRequest $request, Employee $employee, EmployeeBonus $bonus): RedirectResponse
    {
        abort_if($bonus->employee_id !== $employee->id, 404);
        abort_if($bonus->status !== EmployeeBonus::STATUS_PENDING, 422, 'Este bono ya fue aplicado a una planilla y no se puede editar.');

        $bonus->update($request->validated());

        return redirect()
            ->back()
            ->with('success', 'Bono actualizado.');
    }

    public function destroy(Employee $employee, EmployeeBonus $bonus): RedirectResponse
    {
        abort_if($bonus->employee_id !== $employee->id, 404);
        abort_if($bonus->status !== EmployeeBonus::STATUS_PENDING, 422, 'Este bono ya fue aplicado a una planilla.');

        $bonus->delete();

        return redirect()
            ->back()
            ->with('success', 'Bono eliminado.');
    }
}
