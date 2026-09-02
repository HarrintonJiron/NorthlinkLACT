<?php

namespace Tests\Feature\Modules\Admin;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private Permission $modulePermission;

    protected function setUp(): void
    {
        parent::setUp();

        $administrator = User::factory()->make()->forceFill([
            'id' => 999,
            'active' => true,
            'is_admin' => true,
        ]);
        $this->actingAs($administrator);
        $this->modulePermission = Permission::query()->create([
            'name' => 'access_inventory',
            'display_name' => 'Inventario',
            'module' => 'inventory',
        ]);
    }

    public function test_settings_page_exposes_user_management_option(): void
    {
        $this->get(route('settings.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index'));
    }

    public function test_users_page_renders_linked_users_and_available_collaborators(): void
    {
        $role = EmployeeRole::factory()->create(['name' => 'Ruta']);
        $linkedEmployee = Employee::factory()->for($role, 'role')->create([
            'first_name' => 'María',
            'last_name' => 'López',
        ]);
        $availableEmployee = Employee::factory()->for($role, 'role')->create([
            'first_name' => 'Carlos',
            'last_name' => 'Pérez',
        ]);
        User::factory()->create([
            'employee_id' => $linkedEmployee->id,
            'name' => $linkedEmployee->full_name,
            'username' => 'mlopez',
        ]);

        $this->get(route('settings.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Users/Index')
                ->has('users.data', 1, fn (Assert $userProp) => $userProp
                    ->where('username', 'mlopez')
                    ->where('employee.full_name', 'María López')
                    ->where('employee.role.name', 'Ruta')
                    ->etc())
                ->has('availableEmployees', 1, fn (Assert $employeeProp) => $employeeProp
                    ->where('id', $availableEmployee->id)
                    ->where('full_name', 'Carlos Pérez')
                    ->where('role.name', 'Ruta')
                    ->etc())
                ->where('stats.total', 1)
                ->where('stats.active', 1)
                ->where('stats.available', 1));
    }

    public function test_valid_payload_creates_user_from_collaborator_data(): void
    {
        $role = EmployeeRole::factory()->create(['name' => 'Administrativo']);
        $employee = Employee::factory()->for($role, 'role')->create([
            'first_name' => 'Ana',
            'last_name' => 'Martínez',
            'email' => 'ana@example.com',
            'phone' => '8888-7777',
        ]);

        $response = $this->post(route('settings.users.store'), [
            'employee_id' => $employee->id,
            'username' => '  AMARTINEZ  ',
            'password' => 'Clave!Segura2026',
            'password_confirmation' => 'Clave!Segura2026',
            'pin' => '0427',
            'active' => true,
            'permission_ids' => [$this->modulePermission->id],
            'name' => 'Nombre alterado',
            'role_id' => 999,
        ]);

        $response
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('success', 'Usuario creado exitosamente.');

        $createdUser = User::query()->firstOrFail();

        $this->assertSame($employee->id, $createdUser->employee_id);
        $this->assertSame('amartinez', $createdUser->username);
        $this->assertSame('Ana Martínez', $createdUser->name);
        $this->assertSame('ana@example.com', $createdUser->email);
        $this->assertSame('8888-7777', $createdUser->phone);
        $this->assertTrue($createdUser->active);
        $this->assertTrue(Hash::check('Clave!Segura2026', $createdUser->password));
        $this->assertNotNull($createdUser->password_changed_at);
        $this->assertTrue(Hash::check('0427', $createdUser->pin));
        $this->assertDatabaseMissing('role_user', ['user_id' => $createdUser->id]);
        $this->assertDatabaseHas('permission_user', [
            'user_id' => $createdUser->id,
            'permission_id' => $this->modulePermission->id,
        ]);
    }

    public function test_requires_collaborator_username_password_and_pin(): void
    {
        $response = $this->post(route('settings.users.store'), []);

        $response->assertSessionHasErrors([
            'employee_id' => 'Selecciona un colaborador.',
            'username' => 'El nombre de usuario es obligatorio.',
            'password' => 'La contraseña es obligatoria.',
            'pin' => 'El PIN es obligatorio.',
        ]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_rejects_inactive_or_already_assigned_collaborators(): void
    {
        $inactiveEmployee = Employee::factory()->create(['status' => 'retirado']);
        $assignedEmployee = Employee::factory()->create();
        User::factory()->create(['employee_id' => $assignedEmployee->id]);

        $this->post(route('settings.users.store'), $this->validPayload($inactiveEmployee))
            ->assertSessionHasErrors([
                'employee_id' => 'El colaborador seleccionado no está disponible.',
            ]);
        $this->post(route('settings.users.store'), $this->validPayload($assignedEmployee))
            ->assertSessionHasErrors([
                'employee_id' => 'Este colaborador ya tiene un usuario asignado.',
            ]);
        $this->assertSame(1, User::query()->count());
    }

    public function test_rejects_duplicate_or_invalid_username(): void
    {
        User::factory()->create(['username' => 'aperez']);
        $employee = Employee::factory()->create();

        $this->post(route('settings.users.store'), [
            ...$this->validPayload($employee),
            'username' => 'APEREZ',
        ])->assertSessionHasErrors([
            'username' => 'Este nombre de usuario ya está en uso.',
        ]);
        $this->post(route('settings.users.store'), [
            ...$this->validPayload($employee),
            'username' => 'ana pérez',
        ])->assertSessionHasErrors([
            'username' => 'El usuario solo puede contener letras minúsculas, números, punto, guion o guion bajo.',
        ]);
        $this->assertSame(1, User::query()->count());
    }

    #[DataProvider('invalidPins')]
    public function test_rejects_a_pin_that_is_not_exactly_four_digits(string $pin): void
    {
        $employee = Employee::factory()->create();

        $response = $this->post(route('settings.users.store'), [
            ...$this->validPayload($employee),
            'pin' => $pin,
        ]);

        $response->assertSessionHasErrors([
            'pin' => 'El PIN debe contener exactamente 4 dígitos.',
        ]);
        $this->assertSame(0, User::query()->count());
    }

    #[DataProvider('weakPasswords')]
    public function test_rejects_a_password_without_letters_or_numbers(string $password, string $message): void
    {
        $employee = Employee::factory()->create();

        $response = $this->post(route('settings.users.store'), [
            ...$this->validPayload($employee),
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertSessionHasErrors(['password' => $message]);
        $this->assertSame(0, User::query()->count());
    }

    public function test_update_modifies_user_credentials_and_status(): void
    {
        $role = EmployeeRole::factory()->create();
        $employee = Employee::factory()->for($role, 'role')->create();
        $user = User::factory()->create([
            'employee_id' => $employee->id,
            'username' => 'olduser',
            'active' => true,
            'remember_token' => 'previous-token',
        ]);

        $response = $this->put(route('settings.users.update', $user), [
            'username' => 'newuser',
            'password' => 'Nueva!Clave2026',
            'password_confirmation' => 'Nueva!Clave2026',
            'pin' => '9876',
            'active' => false,
            'permission_ids' => [$this->modulePermission->id],
        ]);

        $response
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('success', 'Usuario actualizado exitosamente.');

        $user->refresh();
        $this->assertSame('newuser', $user->username);
        $this->assertFalse($user->active);
        $this->assertTrue(Hash::check('Nueva!Clave2026', $user->password));
        $this->assertNotNull($user->password_changed_at);
        $this->assertNotSame('previous-token', $user->remember_token);
        $this->assertTrue(Hash::check('9876', $user->pin));
        $this->assertTrue($user->hasPermission('access_inventory'));
    }

    public function test_update_preserves_password_and_pin_when_not_provided(): void
    {
        $role = EmployeeRole::factory()->create();
        $employee = Employee::factory()->for($role, 'role')->create();
        $user = User::factory()->create([
            'employee_id' => $employee->id,
            'username' => 'testuser',
            'password' => Hash::make('Anterior!Clave123'),
            'pin' => Hash::make('1234'),
        ]);

        $this->put(route('settings.users.update', $user), [
            'username' => 'updateduser',
            'password' => '',
            'password_confirmation' => '',
            'pin' => '',
            'active' => true,
            'permission_ids' => [$this->modulePermission->id],
        ]);

        $user->refresh();
        $this->assertSame('updateduser', $user->username);
        $this->assertTrue(Hash::check('Anterior!Clave123', $user->password));
        $this->assertTrue(Hash::check('1234', $user->pin));
    }

    public function test_update_can_remove_all_module_permissions(): void
    {
        $user = User::factory()->create(['username' => 'limiteduser']);
        $user->permissions()->attach($this->modulePermission);

        $this->put(route('settings.users.update', $user), [
            'username' => 'limiteduser',
            'password' => '',
            'password_confirmation' => '',
            'pin' => '',
            'active' => true,
            'permission_ids' => [],
        ])->assertRedirect(route('settings.users.index'));

        $this->assertDatabaseMissing('permission_user', [
            'user_id' => $user->id,
            'permission_id' => $this->modulePermission->id,
        ]);
    }

    public function test_update_status_toggles_user_active_state(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->patch(route('settings.users.status.update', $user), ['active' => false])
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('success', 'Usuario desactivado exitosamente.');

        $user->refresh();
        $this->assertFalse($user->active);

        $this->patch(route('settings.users.status.update', $user), ['active' => true])
            ->assertRedirect(route('settings.users.index'))
            ->assertSessionHas('success', 'Usuario activado exitosamente.');

        $user->refresh();
        $this->assertTrue($user->active);
    }

    public function test_update_requires_username(): void
    {
        $user = User::factory()->create();

        $this->put(route('settings.users.update', $user), [])
            ->assertSessionHasErrors([
                'username' => 'El nombre de usuario es obligatorio.',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Employee $employee): array
    {
        return [
            'employee_id' => $employee->id,
            'username' => 'usuario_prueba',
            'password' => 'Clave!Segura2026',
            'password_confirmation' => 'Clave!Segura2026',
            'pin' => '1234',
            'active' => true,
            'permission_ids' => [$this->modulePermission->id],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPins(): array
    {
        return [
            'three digits' => ['123'],
            'five digits' => ['12345'],
            'letters' => ['12a4'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Corta!1Aa', 'La contraseña debe tener al menos 12 caracteres.'],
            'without mixed case' => ['solominusculas!2026', 'La contraseña debe incluir mayúsculas y minúsculas.'],
            'without numbers' => ['SinNumeros!Clave', 'La contraseña debe incluir al menos un número.'],
            'without symbols' => ['SinSimbolo2026Aa', 'La contraseña debe incluir al menos un símbolo.'],
            'too long' => [str_repeat('Aa1!', 64), 'La contraseña no puede superar 255 caracteres.'],
        ];
    }
}
