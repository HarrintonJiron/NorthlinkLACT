<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Requests\StoreUserRequest;
use App\Modules\Admin\Requests\UpdateUserRequest;
use App\Modules\Personnel\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->select(['id', 'employee_id', 'username', 'name', 'email', 'phone', 'active', 'created_at'])
            ->with([
                'employee:id,employee_role_id,first_name,last_name,email,phone',
                'employee.role:id,name',
            ])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $availableEmployees = Employee::query()
            ->select(['id', 'employee_role_id', 'first_name', 'last_name', 'email', 'phone'])
            ->where('status', 'activo')
            ->doesntHave('user')
            ->with('role:id,name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role' => $employee->role,
            ]);

        return Inertia::render('Settings/Users/Index', [
            'users' => $users,
            'availableEmployees' => $availableEmployees,
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('active', true)->count(),
                'available' => $availableEmployees->count(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id']);

        User::query()->create([
            'employee_id' => $employee->id,
            'username' => $data['username'],
            'name' => $employee->full_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'password' => $data['password'],
            'pin' => $data['pin'],
            'active' => $data['active'],
        ]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->update([
            'username' => $data['username'],
            'active' => $data['active'],
        ]);

        if (filled($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        if (filled($data['pin'])) {
            $user->update(['pin' => $data['pin']]);
        }

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $user->update([
            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('settings.users.index')
            ->with('success', $user->active
                ? 'Usuario activado exitosamente.'
                : 'Usuario desactivado exitosamente.');
    }
}
