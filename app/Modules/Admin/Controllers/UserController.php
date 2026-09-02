<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Requests\StoreUserRequest;
use App\Modules\Admin\Requests\UpdateUserRequest;
use App\Modules\Auth\Services\AccountLockoutService;
use App\Modules\Personnel\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly AccountLockoutService $accountLockoutService) {}

    public function index(): Response
    {
        $users = User::query()
            ->select(['id', 'employee_id', 'username', 'name', 'email', 'phone', 'active', 'is_admin', 'created_at'])
            ->with([
                'employee:id,employee_role_id,first_name,last_name,email,phone',
                'employee.role:id,name',
                'permissions:id,name,display_name,module',
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
            'modules' => Permission::query()
                ->select(['id', 'name', 'display_name', 'module'])
                ->where('name', 'like', 'access_%')
                ->orderBy('display_name')
                ->get(),
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

        DB::transaction(function () use ($data, $employee): void {
            $user = User::query()->create([
                'employee_id' => $employee->id,
                'username' => $data['username'],
                'name' => $employee->full_name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'password' => $data['password'],
                'pin' => $data['pin'],
                'active' => $data['active'],
            ]);

            $user->permissions()->sync($data['permission_ids']);
            $this->accountLockoutService->recordPasswordChanged($user);
        });

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $user): void {
            $user->update([
                'username' => $data['username'],
                'active' => $user->is_admin ? true : $data['active'],
            ]);

            if (! $user->is_admin) {
                $user->permissions()->sync($data['permission_ids']);
            }

            if (filled($data['password'])) {
                $user->update(['password' => $data['password']]);
                $this->accountLockoutService->recordPasswordChanged($user);
            }

            if (filled($data['pin'])) {
                $user->update(['pin' => $data['pin']]);
            }
        });

        return redirect()
            ->route('settings.users.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is_admin, 422, 'No se puede desactivar al administrador.');

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
