<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeVacation;
use App\Modules\Personnel\Requests\StoreVacationRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    public function store(StoreVacationRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();
        $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        $employee->vacations()->create([
            ...$data,
            'days' => $days,
            'status' => EmployeeVacation::STATUS_PENDING,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Solicitud de vacaciones registrada.');
    }

    public function update(StoreVacationRequest $request, Employee $employee, EmployeeVacation $vacation): RedirectResponse
    {
        abort_if($vacation->employee_id !== $employee->id, 404);
        abort_if($vacation->status === EmployeeVacation::STATUS_REJECTED, 422, 'Una solicitud rechazada no se puede editar; crea una nueva.');

        $data = $request->validated();
        $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $wasApproved = $vacation->status === EmployeeVacation::STATUS_APPROVED;

        $vacation->update([
            ...$data,
            'days' => $days,
            'status' => EmployeeVacation::STATUS_PENDING,
            'approved_at' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', $wasApproved
                ? 'Solicitud actualizada; vuelve a aprobarla porque cambiaron las fechas.'
                : 'Solicitud de vacaciones actualizada.');
    }

    public function updateStatus(Request $request, Employee $employee, EmployeeVacation $vacation): RedirectResponse
    {
        abort_if($vacation->employee_id !== $employee->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        if ($validated['status'] === EmployeeVacation::STATUS_APPROVED && $vacation->paid) {
            abort_if(
                $vacation->days > $employee->vacationBalance(),
                422,
                "Este colaborador solo tiene {$employee->vacationBalance()} día(s) de vacaciones disponibles; esta solicitud pide {$vacation->days}."
            );
        }

        $vacation->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === EmployeeVacation::STATUS_APPROVED ? now() : null,
        ]);

        return redirect()
            ->back()
            ->with('success', $validated['status'] === EmployeeVacation::STATUS_APPROVED
                ? 'Vacaciones aprobadas exitosamente.'
                : 'Vacaciones rechazadas.');
    }

    public function destroy(Employee $employee, EmployeeVacation $vacation): RedirectResponse
    {
        abort_if($vacation->employee_id !== $employee->id, 404);
        abort_if($vacation->status !== EmployeeVacation::STATUS_PENDING, 422, 'Solo se pueden eliminar solicitudes pendientes.');

        $vacation->delete();

        return redirect()
            ->back()
            ->with('success', 'Solicitud de vacaciones eliminada.');
    }
}
