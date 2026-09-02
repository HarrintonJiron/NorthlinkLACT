<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'inertia.pages.paths' => [resource_path('js/Pages')],
        ]);
    }

    protected function authenticate(?User $user = null): User
    {
        $user ??= User::factory()->create(['active' => true, 'is_admin' => true]);

        // Forzar is_admin en la base de datos
        $user->is_admin = true;
        $user->save();

        $this->be($user);

        return $user;
    }
}
