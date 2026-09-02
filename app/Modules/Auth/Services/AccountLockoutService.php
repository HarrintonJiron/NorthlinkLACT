<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountLockoutService
{
    public function isLocked(User $user): bool
    {
        return $user->locked_until?->isFuture() ?? false;
    }

    public function recordFailedAttempt(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());
            $failedAttempts = $lockedUser->failed_login_attempts + 1;

            $lockedUser->forceFill([
                'failed_login_attempts' => $failedAttempts,
                'last_failed_login_at' => now(),
                'locked_until' => $this->lockoutExpiresAt($failedAttempts),
            ])->save();

            return $lockedUser;
        }, attempts: 3);
    }

    public function recordSuccessfulAuthentication(User $user): User
    {
        return $this->updateSecurityState($user, [
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);
    }

    public function recordPasswordChanged(User $user): User
    {
        return $this->updateSecurityState($user, [
            'failed_login_attempts' => 0,
            'last_failed_login_at' => null,
            'locked_until' => null,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateSecurityState(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            $lockedUser->forceFill($attributes)->save();

            return $lockedUser;
        }, attempts: 3);
    }

    private function lockoutExpiresAt(int $failedAttempts): ?CarbonInterface
    {
        $threshold = (int) config('auth_security.lockout.threshold');

        if ($failedAttempts < $threshold) {
            return null;
        }

        $baseSeconds = (int) config('auth_security.lockout.base_seconds');
        $maxSeconds = (int) config('auth_security.lockout.max_seconds');
        $progressionStep = min($failedAttempts - $threshold, 20);
        $lockoutSeconds = min($baseSeconds * (2 ** $progressionStep), $maxSeconds);

        return now()->addSeconds($lockoutSeconds);
    }
}
