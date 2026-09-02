<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as LaravelRoute;
use Tests\TestCase;

class AuthenticatedRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_operational_web_routes(): void
    {
        foreach (['/', '/routes', '/sumni', '/inventory', '/settings'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_every_non_public_route_has_authentication_and_active_user_middleware(): void
    {
        $publicUris = ['login', 'api/offline/login', 'up', 'sanctum/csrf-cookie', 'storage/{path}'];

        /** @var LaravelRoute $route */
        foreach (app('router')->getRoutes() as $route) {
            if (in_array($route->uri(), $publicUris, true)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            if (str_starts_with($route->uri(), 'api/')) {
                $this->assertContains('auth:sanctum', $middleware, $route->uri());
            } else {
                $this->assertContains('auth', $middleware, $route->uri());
            }

            $this->assertContains('user.active', $middleware, $route->uri());
        }
    }

    public function test_inactive_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->create(['active' => false]);
        $this->actingAs($user);

        $this->get('/')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
