<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAbsence;
use App\Modules\Personnel\Requests\StoreAbsenceRequest;
use Illuminate\Http\RedirectResponse;

class AbsenceController extends Controller
{
    public function store(StoreAbsenceRequest $request, Employee $employee): RedirectResponse
    {
        $employee->absences()->updateOrCreate(
            ['date' => $request->validated('date')],
            $request->validated()
        );

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Ausencia registrada.');
    }

    public function destroy(Employee $employee, EmployeeAbsence $absence): RedirectResponse
    {
        abort_if($absence->employee_id !== $employee->id, 404);

        $absence->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Ausencia eliminada.');
    }
}
