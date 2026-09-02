<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_access_assigned_modules(): void
    {
        $permission = Permission::query()->create([
            'name' => 'access_production',
            'display_name' => 'Producción',
            'module' => 'production',
        ]);
        $user = User::factory()->create(['active' => true]);
        $user->permissions()->attach($permission);

        $this->actingAs($user)
            ->get(route('production.index'))
            ->assertOk();

        $this->get(route('reports.index'))->assertForbidden();
    }

    public function test_administrator_has_full_access_without_assigned_permissions(): void
    {
        $administrator = User::factory()->create([
            'active' => true,
            'is_admin' => true,
        ]);

        $this->actingAs($administrator)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_non_administrator_cannot_manage_users(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)
            ->get(route('settings.users.index'))
            ->assertForbidden();
    }

    public function test_inactive_user_session_is_invalidated(): void
    {
        $user = User::factory()->create(['active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
