<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_active_user_can_authenticate_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'usuario.seguro',
            'password' => 'Clave!Segura2026',
            'active' => true,
        ]);

        $this->post(route('login.store'), [
            'username' => '  USUARIO.SEGURO  ',
            'password' => 'Clave!Segura2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'failed_login_attempts' => 0,
        ]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_invalid_password_uses_generic_error_and_records_failure(): void
    {
        $user = User::factory()->create([
            'username' => 'usuario.seguro',
            'password' => 'Clave!Segura2026',
        ]);

        $this->post(route('login.store'), [
            'username' => 'usuario.seguro',
            'password' => 'Incorrecta!2026',
        ])->assertSessionHasErrors([
            'username' => 'Las credenciales proporcionadas no son válidas.',
        ]);

        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->failed_login_attempts);
    }

    public function test_unknown_user_uses_same_generic_error(): void
    {
        $this->post(route('login.store'), [
            'username' => 'no.existe',
            'password' => 'Incorrecta!2026',
        ])->assertSessionHasErrors([
            'username' => 'Las credenciales proporcionadas no son válidas.',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'username' => 'usuario.inactivo',
            'password' => 'Clave!Segura2026',
            'active' => false,
        ]);

        $this->post(route('login.store'), [
            'username' => 'usuario.inactivo',
            'password' => 'Clave!Segura2026',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_account_is_locked_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'username' => 'usuario.seguro',
            'password' => 'Clave!Segura2026',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'username' => 'usuario.seguro',
                'password' => 'Incorrecta!2026',
            ]);
        }

        $user->refresh();
        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertGreaterThan(now()->timestamp, $user->locked_until->timestamp);

        $this->post(route('login.store'), [
            'username' => 'usuario.seguro',
            'password' => 'Clave!Segura2026',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_private_routes_redirect_guests_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
