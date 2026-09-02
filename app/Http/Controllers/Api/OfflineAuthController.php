<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OfflineLoginRequest;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OfflineAuthController extends Controller
{
    public function store(OfflineLoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('username', $request->validated('username'))
            ->first();

        if (! $user || ! $user->active || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales no son correctas o la cuenta está inactiva.'],
            ]);
        }

        $device = Device::query()
            ->where('device_uuid', $request->validated('device_uuid'))
            ->first();

        if ($device && $device->user_id !== $user->id) {
            return response()->json([
                'message' => 'El dispositivo ya está vinculado a otra cuenta.',
            ], 409);
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
        $token = $user->createToken($tokenName, ['offline:sync']);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
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
