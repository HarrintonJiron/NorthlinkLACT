<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Roles', [
            'roles' => Role::query()->with(['permissions:id,name,display_name,module', 'users:id,name,username,active'])->withCount('users')->orderBy('display_name')->get(),
            'permissions' => Permission::query()->where('name', 'like', 'access_%')->orderBy('module')->orderBy('display_name')->get(['id', 'name', 'display_name', 'module']),
            'users' => User::query()->where('is_admin', false)->orderBy('name')->get(['id', 'name', 'username', 'active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);
        $role = Role::query()->create(['name' => $data['name'], 'display_name' => $data['display_name'], 'description' => $data['description'] ?? null, 'guard_name' => 'web']);
        $role->permissions()->sync($data['permission_ids']);
        $role->users()->sync($data['user_ids'] ?? []);
        $this->audit($role, 'created');

        return back()->with('success', 'Rol creado y asignado correctamente.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validateRole($request, $role);
        $old = $role->load('permissions', 'users')->toArray();
        $role->update(['name' => $data['name'], 'display_name' => $data['display_name'], 'description' => $data['description'] ?? null]);
        $role->permissions()->sync($data['permission_ids']);
        $role->users()->sync($data['user_ids'] ?? []);
        $this->audit($role, 'updated', $old);

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'No se puede eliminar un rol asignado a usuarios.']);
        }
        $this->audit($role, 'deleted');
        $role->delete();

        return back()->with('success', 'Rol eliminado.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'name')->ignore($role)],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_ids' => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ]);

        if (User::query()->whereIn('id', $data['user_ids'] ?? [])->where('is_admin', true)->exists()) {
            throw ValidationException::withMessages(['user_ids' => 'El administrador principal no se asigna a roles limitados.']);
        }

        return $data;
    }

    private function audit(Role $role, string $action, ?array $old = null): void
    {
        AuditEvent::query()->create(['user_id' => request()->user()->id, 'entity_type' => Role::class, 'entity_id' => $role->id, 'action' => $action, 'description' => "Rol {$role->display_name}: {$action}", 'old_values' => $old, 'new_values' => $role->exists ? $role->load('permissions', 'users')->toArray() : null, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }
}
