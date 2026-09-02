<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeLeave;
use App\Modules\Personnel\Requests\StoreLeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function store(StoreLeaveRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();
        $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        $employee->leaves()->create([
            ...$data,
            'days' => $days,
            'status' => EmployeeLeave::STATUS_PENDING,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Solicitud de permiso registrada.');
    }

    public function update(StoreLeaveRequest $request, Employee $employee, EmployeeLeave $leave): RedirectResponse
    {
        abort_if($leave->employee_id !== $employee->id, 404);
        abort_if($leave->status === EmployeeLeave::STATUS_REJECTED, 422, 'Un permiso rechazado no se puede editar; crea uno nuevo.');

        $data = $request->validated();
        $days = Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;
        $wasApproved = $leave->status === EmployeeLeave::STATUS_APPROVED;

        $leave->update([
            ...$data,
            'days' => $days,
            'status' => EmployeeLeave::STATUS_PENDING,
            'approved_at' => null,
        ]);

        return redirect()
            ->back()
            ->with('success', $wasApproved
                ? 'Permiso actualizado; vuelve a aprobarlo porque cambiaron las fechas.'
                : 'Solicitud de permiso actualizada.');
    }

    public function updateStatus(Request $request, Employee $employee, EmployeeLeave $leave): RedirectResponse
    {
        abort_if($leave->employee_id !== $employee->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $leave->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === EmployeeLeave::STATUS_APPROVED ? now() : null,
        ]);

        return redirect()
            ->back()
            ->with('success', $validated['status'] === EmployeeLeave::STATUS_APPROVED
                ? 'Permiso aprobado exitosamente.'
                : 'Permiso rechazado.');
    }

    public function destroy(Employee $employee, EmployeeLeave $leave): RedirectResponse
    {
        abort_if($leave->employee_id !== $employee->id, 404);
        abort_if($leave->status !== EmployeeLeave::STATUS_PENDING, 422, 'Solo se pueden eliminar permisos pendientes.');

        $leave->delete();

        return redirect()
            ->back()
            ->with('success', 'Solicitud de permiso eliminada.');
    }
}
