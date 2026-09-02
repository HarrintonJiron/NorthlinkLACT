<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\AccountLockoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    private const DUMMY_PASSWORD_HASH = '$2y$12$8uIr2x9Imf6OcCdB2R4oROGpYxkhiJf2iH2dNLIqwPYM2tEgfvUre';

    public function __construct(private readonly AccountLockoutService $accountLockoutService) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('username', $credentials['username'])->first();

        if (! $user) {
            Hash::check($credentials['password'], self::DUMMY_PASSWORD_HASH);
            $this->failAuthentication();
        }

        if ($this->accountLockoutService->isLocked($user)) {
            Hash::check($credentials['password'], self::DUMMY_PASSWORD_HASH);
            $this->failAuthentication();
        }

        if (! $user->active || ! Hash::check($credentials['password'], $user->password)) {
            $this->accountLockoutService->recordFailedAttempt($user);
            $this->failAuthentication();
        }

        $this->accountLockoutService->recordSuccessfulAuthentication($user);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * @return never
     */
    private function failAuthentication(): void
    {
        throw ValidationException::withMessages([
            'username' => 'Las credenciales proporcionadas no son válidas.',
        ]);
    }
}
