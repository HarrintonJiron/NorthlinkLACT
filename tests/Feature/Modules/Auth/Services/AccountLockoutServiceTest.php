<?php

namespace Tests\Feature\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Services\AccountLockoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountLockoutServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_locks_account_on_configured_failed_attempt(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $user = User::factory()->create();
        $service = new AccountLockoutService;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $user = $service->recordFailedAttempt($user);
        }

        $this->assertSame(5, $user->failed_login_attempts);
        $this->assertSame('2026-09-01 12:00:00', $user->last_failed_login_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 12:01:00', $user->locked_until?->format('Y-m-d H:i:s'));
        $this->assertTrue($service->isLocked($user));
    }

    public function test_does_not_lock_account_before_configured_failed_attempt(): void
    {
        $user = User::factory()->create();
        $service = new AccountLockoutService;

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $user = $service->recordFailedAttempt($user);
        }

        $this->assertSame(4, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertFalse($service->isLocked($user));
    }

    public function test_increases_lockout_period_after_additional_failure(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $user = User::factory()->create();
        $service = new AccountLockoutService;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $user = $service->recordFailedAttempt($user);
        }
        $this->travelTo('2026-09-01 12:01:01');
        $user = $service->recordFailedAttempt($user);

        $this->assertSame(6, $user->failed_login_attempts);
        $this->assertSame('2026-09-01 12:03:01', $user->locked_until?->format('Y-m-d H:i:s'));
    }

    public function test_successful_authentication_clears_failures_and_records_login(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $user = User::factory()->create([
            'failed_login_attempts' => 7,
            'locked_until' => now()->subMinute(),
            'last_failed_login_at' => now()->subMinutes(2),
        ]);
        $service = new AccountLockoutService;

        $user = $service->recordSuccessfulAuthentication($user);

        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNull($user->last_failed_login_at);
        $this->assertSame('2026-09-01 12:00:00', $user->last_login_at?->format('Y-m-d H:i:s'));
    }

    public function test_password_change_clears_lockout_and_rotates_remember_token(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $user = User::factory()->create([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinute(),
            'last_failed_login_at' => now(),
            'remember_token' => 'previous-token',
        ]);
        $service = new AccountLockoutService;

        $user = $service->recordPasswordChanged($user);

        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNull($user->last_failed_login_at);
        $this->assertSame('2026-09-01 12:00:00', $user->password_changed_at?->format('Y-m-d H:i:s'));
        $this->assertNotSame('previous-token', $user->remember_token);
        $this->assertSame(60, strlen((string) $user->remember_token));
    }
}
