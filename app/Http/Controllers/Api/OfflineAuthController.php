<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OfflineLoginRequest;
use App\Models\Device;
use App\Models\User;
use App\Modules\Auth\Services\AccountLockoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OfflineAuthController extends Controller
{
    public function __construct(private AccountLockoutService $accountLockoutService) {}

    public function store(OfflineLoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('username', $request->validated('username'))
            ->first();

        if (! $user || ! $user->active) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales no son correctas o la cuenta está inactiva.'],
            ]);
        }

        if ($this->accountLockoutService->isLocked($user)) {
            throw ValidationException::withMessages([
                'username' => ['La cuenta está temporalmente bloqueada. Intenta nuevamente más tarde.'],
            ]);
        }

        if (! Hash::check($request->validated('password'), $user->password)) {
            $this->accountLockoutService->recordFailedAttempt($user);

            throw ValidationException::withMessages([
                'username' => ['Las credenciales no son correctas o la cuenta está inactiva.'],
            ]);
        }

        $device = Device::query()
            ->where('device_uuid', $request->validated('device_uuid'))
            ->first();

        if ($device && ! $device->active) {
            return response()->json([
                'message' => 'El dispositivo fue desactivado. Un administrador debe autorizarlo nuevamente.',
            ], 403);
        }

        if ($device && $device->user_id !== $user->id) {
            $device->user?->tokens()
                ->where('name', 'device:'.$device->device_uuid)
                ->delete();
        }

        $device = Device::query()->updateOrCreate(
            ['device_uuid' => $request->validated('device_uuid')],
            [
                'user_id' => $user->id,
                'name' => $request->validated('device_name'),
                'platform' => $request->validated('platform'),
                'active' => true,
                'last_seen_at' => now(),
            ],
        );

        $tokenName = 'device:'.$device->device_uuid;
        $user->tokens()->where('name', $tokenName)->delete();
        $this->accountLockoutService->recordSuccessfulAuthentication($user);
        $token = $user->createToken(
            $tokenName,
            ['offline:sync'],
            now()->addDays((int) config('auth_security.offline_token_days')),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'device' => [
                'uuid' => $device->device_uuid,
                'name' => $device->name,
                'platform' => $device->platform,
            ],
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
            ],
        ]);
    }
}
