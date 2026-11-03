<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Admin\Models\Role;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAndAuditManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_update_and_assign_reusable_role(): void
    {
        $admin = $this->authenticate();
        $user = User::factory()->create(['is_admin' => false]);
        $inventory = Permission::query()->create(['name' => 'access_inventory', 'display_name' => 'Inventario', 'module' => 'inventory']);
        $production = Permission::query()->create(['name' => 'access_production', 'display_name' => 'Producción', 'module' => 'production']);

        $this->post(route('settings.roles.store'), [
            'name' => 'bodeguero', 'display_name' => 'Bodeguero', 'description' => 'Control de almacén',
            'permission_ids' => [$inventory->id], 'user_ids' => [$user->id],
        ])->assertRedirect()->assertSessionHas('success');

        $role = Role::query()->firstOrFail();
        $this->assertTrue($user->fresh()->hasPermission('access_inventory'));
        $this->assertFalse($user->fresh()->hasPermission('access_production'));

        $this->put(route('settings.roles.update', $role), [
            'name' => 'jefe_bodega', 'display_name' => 'Jefe de bodega',
            'permission_ids' => [$inventory->id, $production->id], 'user_ids' => [$user->id],
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertTrue($user->fresh()->hasPermission('access_production'));
        $this->assertDatabaseHas('audit_events', ['user_id' => $admin->id, 'entity_type' => Role::class, 'action' => 'updated']);

        $this->from(route('settings.roles.index'))->delete(route('settings.roles.destroy', $role))
            ->assertRedirect(route('settings.roles.index'))->assertSessionHasErrors(['role']);
    }

    public function test_audit_screen_is_paginated_filterable_and_admin_only(): void
    {
        $admin = $this->authenticate();
        AuditEvent::query()->create(['user_id' => $admin->id, 'entity_type' => Role::class, 'entity_id' => 99, 'action' => 'updated', 'description' => 'Cambio de permisos crítico']);

        $this->get(route('settings.audit.index', ['search' => 'permisos', 'action' => 'updated']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Settings/Audit')->has('events.data', 1)->where('events.data.0.action', 'updated'));

        $nonAdmin = User::factory()->create(['is_admin' => false, 'active' => true]);
        $this->actingAs($nonAdmin)->get(route('settings.audit.index'))->assertForbidden();
    }
}
