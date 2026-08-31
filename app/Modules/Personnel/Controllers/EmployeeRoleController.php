<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Requests\StoreEmployeeRoleRequest;
use App\Modules\Personnel\Requests\UpdateEmployeeRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeRoleController extends Controller
{
    public function store(StoreEmployeeRoleRequest $request): RedirectResponse
    {
        EmployeeRole::query()->create($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Rol creado exitosamente.');
    }

    public function update(UpdateEmployeeRoleRequest $request, EmployeeRole $employeeRole): RedirectResponse
    {
        $employeeRole->update($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Rol actualizado exitosamente.');
    }

    public function updateStatus(Request $request, EmployeeRole $employeeRole): RedirectResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $employeeRole->update([
            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', $employeeRole->active
                ? 'Rol habilitado exitosamente.'
                : 'Rol deshabilitado exitosamente.');
    }
}
