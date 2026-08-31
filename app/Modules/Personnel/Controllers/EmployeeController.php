<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Requests\StoreEmployeeRequest;
use App\Modules\Personnel\Requests\UpdateEmployeeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        $employees = Employee::query()
            ->select([
                'id',
                'employee_role_id',
                'full_name',
                'identity_number',
                'email',
                'phone',
                'hired_at',
                'active',
                'created_at',
            ])
            ->with('role:id,name')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $roles = EmployeeRole::query()
            ->select(['id', 'name', 'description', 'active'])
            ->withCount('employees')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Personnel/Index', [
            'employees' => $employees,
            'roles' => $roles,
            'stats' => [
                'total' => Employee::query()->count(),
                'active' => Employee::query()->where('active', true)->count(),
                'inactive' => Employee::query()->where('active', false)->count(),
                'roles' => EmployeeRole::query()->where('active', true)->count(),
            ],
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::query()->create($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Colaborador creado exitosamente.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Colaborador actualizado exitosamente.');
    }

    public function updateStatus(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $employee->update([
            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', $employee->active
                ? 'Colaborador activado exitosamente.'
                : 'Colaborador desactivado exitosamente.');
    }
}
