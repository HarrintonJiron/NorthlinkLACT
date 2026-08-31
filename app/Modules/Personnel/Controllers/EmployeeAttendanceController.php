<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Requests\StoreEmployeeAttendanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EmployeeAttendanceController extends Controller
{
    public function store(StoreEmployeeAttendanceRequest $request, ?Employee $employee = null): RedirectResponse
    {
        $data = $request->validated();
        $targetEmployeeId = $employee?->id ?? $data['employee_id'];

        $payload = [
            'employee_id' => $targetEmployeeId,
            'attendance_date' => $data['attendance_date'],
            'type' => $data['type'],
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if ($request->hasFile('justification')) {
            $file = $request->file('justification');
            $path = $file->store("employee-attendances/{$targetEmployeeId}", 'public');
            $payload['justification_path'] = $path;
            $payload['justification_name'] = $file->getClientOriginalName();
        }

        $attendance = EmployeeAttendance::query()
            ->where('employee_id', $targetEmployeeId)
            ->whereDate('attendance_date', $data['attendance_date'])
            ->first();

        if ($attendance) {
            $attendance->update($payload);
        } else {
            EmployeeAttendance::query()->create($payload);
        }

        return redirect()
            ->route('employees.show', $targetEmployeeId)
            ->with('success', 'Asistencia registrada correctamente.');
    }

    public function destroy(Employee $employee, EmployeeAttendance $attendance): RedirectResponse
    {
        abort_unless($attendance->employee_id === $employee->id, 404);

        if ($attendance->justification_path) {
            Storage::disk('public')->delete($attendance->justification_path);
        }

        $attendance->delete();

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Asistencia eliminada correctamente.');
    }
}
