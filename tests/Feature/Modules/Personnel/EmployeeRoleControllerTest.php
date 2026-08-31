<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeRoleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_creates_a_role(): void
    {
        $response = $this->post(route('employees.roles.store'), [
            'name' => '  Responsable de acopio  ',
            'description' => '  Coordina las visitas de la ruta.  ',
        ]);

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Rol creado exitosamente.');

        $role = EmployeeRole::query()->firstOrFail();

        $this->assertSame('Responsable de acopio', $role->name);
        $this->assertSame('Coordina las visitas de la ruta.', $role->description);
        $this->assertTrue($role->active);
    }

    public function test_requires_a_name(): void
    {
        $response = $this->post(route('employees.roles.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'El nombre del rol es obligatorio.',
        ]);
        $this->assertSame(0, EmployeeRole::query()->count());
    }

    public function test_rejects_a_duplicate_role_name(): void
    {
        EmployeeRole::factory()->create(['name' => 'Conductor']);

        $response = $this->post(route('employees.roles.store'), [
            'name' => 'Conductor',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Ya existe un rol con este nombre.',
        ]);
        $this->assertSame(1, EmployeeRole::query()->count());
    }

    public function test_valid_payload_updates_a_role(): void
    {
        $role = EmployeeRole::factory()->create([
            'name' => 'Conductor',
            'description' => null,
        ]);

        $response = $this->put(route('employees.roles.update', $role), [
            'name' => '  Responsable de ruta  ',
            'description' => '  Coordina al equipo de campo.  ',
        ]);

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Rol actualizado exitosamente.');

        $role->refresh();

        $this->assertSame('Responsable de ruta', $role->name);
        $this->assertSame('Coordina al equipo de campo.', $role->description);
    }

    public function test_update_rejects_the_name_of_another_role(): void
    {
        EmployeeRole::factory()->create(['name' => 'Administrativo']);
        $role = EmployeeRole::factory()->create(['name' => 'Ruta']);

        $response = $this->put(route('employees.roles.update', $role), [
            'name' => 'Administrativo',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'Ya existe un rol con este nombre.',
        ]);
        $this->assertSame('Ruta', $role->fresh()->name);
    }

    public function test_role_can_be_disabled_and_enabled(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);

        $this->patch(route('employees.roles.status.update', $role), ['active' => false])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Rol deshabilitado exitosamente.');

        $this->assertFalse($role->fresh()->active);

        $this->patch(route('employees.roles.status.update', $role), ['active' => true])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Rol habilitado exitosamente.');

        $this->assertTrue($role->fresh()->active);
    }

    public function test_status_update_requires_a_boolean_value(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);

        $response = $this->patch(route('employees.roles.status.update', $role), [
            'active' => 'invalid',
        ]);

        $response->assertSessionHasErrors('active');
        $this->assertTrue($role->fresh()->active);
    }
}
