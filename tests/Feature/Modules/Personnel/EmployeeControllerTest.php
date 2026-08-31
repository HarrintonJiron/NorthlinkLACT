<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_page_renders_active_and_inactive_roles_with_totals(): void
    {
        $administrativeRole = EmployeeRole::factory()->create([
            'name' => 'Contabilidad',
        ]);
        $routeRole = EmployeeRole::factory()->create([
            'name' => 'Acopiador',
            'active' => false,
        ]);
        Employee::factory()->for($administrativeRole, 'role')->create(['full_name' => 'Ana Pérez']);
        Employee::factory()->for($routeRole, 'role')->create(['full_name' => 'Carlos López']);

        $this->get(route('employees.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Personnel/Index')
                ->has('employees.data', 2)
                ->has('roles', 2)
                ->where('roles.1.active', false)
                ->where('stats.total', 2)
                ->where('stats.active', 2)
                ->where('stats.inactive', 0)
                ->where('stats.roles', 1));
    }

    public function test_valid_payload_creates_an_active_collaborator_with_a_role(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);

        $response = $this->post(route('employees.store'), [
            'full_name' => '  María González  ',
            'employee_role_id' => $role->id,
            'identity_number' => '001-010190-0001A',
            'email' => 'MARIA@EXAMPLE.COM',
            'phone' => '8888-7777',
            'hired_at' => '2026-08-01',
            'active' => true,
        ]);

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Colaborador creado exitosamente.');

        $employee = Employee::query()->firstOrFail();

        $this->assertSame('María González', $employee->full_name);
        $this->assertSame('maria@example.com', $employee->email);
        $this->assertSame($role->id, $employee->employee_role_id);
        $this->assertTrue($employee->active);
        $this->assertSame('2026-08-01', $employee->hired_at->toDateString());
    }

    public function test_requires_name_and_an_available_role(): void
    {
        $response = $this->post(route('employees.store'), []);

        $response->assertSessionHasErrors([
            'full_name' => 'El nombre completo es obligatorio.',
            'employee_role_id' => 'Selecciona un rol para el colaborador.',
        ]);
        $this->assertSame(0, Employee::query()->count());
    }

    public function test_rejects_an_inactive_role(): void
    {
        $role = EmployeeRole::factory()->create(['active' => false]);

        $response = $this->post(route('employees.store'), [
            'full_name' => 'Carlos López',
            'employee_role_id' => $role->id,
            'active' => true,
        ]);

        $response->assertSessionHasErrors([
            'employee_role_id' => 'El rol seleccionado no está disponible.',
        ]);
        $this->assertDatabaseMissing('employees', ['full_name' => 'Carlos López']);
    }

    public function test_rejects_duplicate_identity_and_email(): void
    {
        $role = EmployeeRole::factory()->create();
        Employee::factory()->for($role, 'role')->create([
            'identity_number' => '001-010190-0001A',
            'email' => 'maria@example.com',
        ]);

        $response = $this->post(route('employees.store'), [
            'full_name' => 'Otra persona',
            'employee_role_id' => $role->id,
            'identity_number' => '001-010190-0001A',
            'email' => 'MARIA@EXAMPLE.COM',
            'active' => true,
        ]);

        $response->assertSessionHasErrors([
            'identity_number' => 'Ya existe un colaborador con esta identificación.',
            'email' => 'Ya existe un colaborador con este correo electrónico.',
        ]);
        $this->assertSame(1, Employee::query()->count());
    }

    public function test_valid_payload_updates_a_collaborator(): void
    {
        $originalRole = EmployeeRole::factory()->create();
        $newRole = EmployeeRole::factory()->create();
        $employee = Employee::factory()->for($originalRole, 'role')->create([
            'full_name' => 'María González',
            'identity_number' => '001-010190-0001A',
            'email' => 'maria@example.com',
        ]);

        $response = $this->put(route('employees.update', $employee), [
            'full_name' => '  María López  ',
            'employee_role_id' => $newRole->id,
            'identity_number' => '001-010190-0001A',
            'email' => 'MARIA@EXAMPLE.COM',
            'phone' => '7777-8888',
            'hired_at' => '2026-08-15',
            'active' => true,
        ]);

        $response
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Colaborador actualizado exitosamente.');

        $employee->refresh();

        $this->assertSame('María López', $employee->full_name);
        $this->assertSame('maria@example.com', $employee->email);
        $this->assertSame($newRole->id, $employee->employee_role_id);
        $this->assertSame('7777-8888', $employee->phone);
    }

    public function test_update_rejects_identity_and_email_used_by_another_collaborator(): void
    {
        $role = EmployeeRole::factory()->create();
        Employee::factory()->for($role, 'role')->create([
            'identity_number' => '001-010190-0001A',
            'email' => 'maria@example.com',
        ]);
        $employee = Employee::factory()->for($role, 'role')->create();

        $response = $this->put(route('employees.update', $employee), [
            'full_name' => 'Carlos López',
            'employee_role_id' => $role->id,
            'identity_number' => '001-010190-0001A',
            'email' => 'MARIA@EXAMPLE.COM',
            'active' => true,
        ]);

        $response->assertSessionHasErrors([
            'identity_number' => 'Ya existe un colaborador con esta identificación.',
            'email' => 'Ya existe un colaborador con este correo electrónico.',
        ]);
        $this->assertNotSame('Carlos López', $employee->fresh()->full_name);
    }

    public function test_collaborator_with_an_inactive_role_can_update_other_information(): void
    {
        $role = EmployeeRole::factory()->create(['active' => false]);
        $employee = Employee::factory()->for($role, 'role')->create();

        $response = $this->put(route('employees.update', $employee), [
            'full_name' => 'Nombre actualizado',
            'employee_role_id' => $role->id,
            'active' => true,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertSame('Nombre actualizado', $employee->fresh()->full_name);
    }

    public function test_update_rejects_a_different_inactive_role(): void
    {
        $currentRole = EmployeeRole::factory()->create();
        $inactiveRole = EmployeeRole::factory()->create(['active' => false]);
        $employee = Employee::factory()->for($currentRole, 'role')->create();

        $response = $this->put(route('employees.update', $employee), [
            'full_name' => 'Carlos López',
            'employee_role_id' => $inactiveRole->id,
            'active' => true,
        ]);

        $response->assertSessionHasErrors([
            'employee_role_id' => 'El rol seleccionado no está disponible.',
        ]);
        $this->assertSame($currentRole->id, $employee->fresh()->employee_role_id);
    }

    public function test_collaborator_can_be_deactivated_and_activated(): void
    {
        $employee = Employee::factory()->create(['active' => true]);

        $this->patch(route('employees.status.update', $employee), ['active' => false])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Colaborador desactivado exitosamente.');

        $this->assertFalse($employee->fresh()->active);

        $this->patch(route('employees.status.update', $employee), ['active' => true])
            ->assertRedirect(route('employees.index'))
            ->assertSessionHas('success', 'Colaborador activado exitosamente.');

        $this->assertTrue($employee->fresh()->active);
    }

    public function test_status_update_requires_a_boolean_value(): void
    {
        $employee = Employee::factory()->create(['active' => true]);

        $response = $this->patch(route('employees.status.update', $employee), [
            'active' => 'invalid',
        ]);

        $response->assertSessionHasErrors('active');
        $this->assertTrue($employee->fresh()->active);
    }
}
