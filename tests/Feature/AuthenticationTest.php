<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_active_user_can_login_with_normalized_username(): void
    {
        $user = User::factory()->create([
            'username' => 'operador',
            'password' => Hash::make('ClaveSegura2026'),
            'active' => true,
        ]);

        $this->post(route('login.store'), [
            'username' => '  OPERADOR ',
            'password' => 'ClaveSegura2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        User::factory()->create([
            'username' => 'operador',
            'password' => Hash::make('ClaveSegura2026'),
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'username' => 'operador',
            'password' => 'incorrecta',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'username' => 'inactivo',
            'password' => Hash::make('ClaveSegura2026'),
            'active' => false,
        ]);

        $this->post(route('login.store'), [
            'username' => 'inactivo',
            'password' => 'ClaveSegura2026',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
