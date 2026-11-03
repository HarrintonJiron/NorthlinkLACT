<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOfflineDevice
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        $tokenName = $token?->name;

        if (! $user || ! $token || ! $token->can('offline:sync') || ! is_string($tokenName) || ! str_starts_with($tokenName, 'device:')) {
            return $this->denied();
        }

        if ($user->locked_until?->isFuture()
            || ($user->password_changed_at && $token->created_at->lte($user->password_changed_at))) {
            $token->delete();

            return $this->denied();
        }

        $deviceUuid = substr($tokenName, strlen('device:'));
        $device = Device::query()
            ->where('device_uuid', $deviceUuid)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (! $device) {
            $token->delete();

            return $this->denied();
        }

        $requestedDeviceUuid = $request->input('device_uuid');
        if ($requestedDeviceUuid !== null && $requestedDeviceUuid !== $deviceUuid) {
            return $this->denied();
        }

        $device->update(['last_seen_at' => now()]);
        $request->attributes->set('offline_device', $device);

        return $next($request);
    }

    private function denied(): JsonResponse
    {
        return response()->json([
            'message' => 'El acceso offline del dispositivo no está autorizado. Inicia sesión nuevamente.',
        ], 403);
    }
}
