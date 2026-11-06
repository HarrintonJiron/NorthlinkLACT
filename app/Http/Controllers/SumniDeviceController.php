<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Http\RedirectResponse;

class SumniDeviceController extends Controller
{
    public function toggle(Device $device): RedirectResponse
    {
        abort_unless(request()->user()?->is_admin, 403);

        $old = $device->only(['active', 'user_id']);
        $device->update(['active' => ! $device->active]);

        AuditEvent::query()->create([
            'user_id' => request()->user()->id,
            'entity_type' => Device::class,
            'entity_id' => $device->id,
            'action' => $device->active ? 'device_activated' : 'device_deactivated',
            'description' => ($device->active ? 'Dispositivo SUNMI activado' : 'Dispositivo SUNMI desactivado').": {$device->name}",
            'old_values' => $old,
            'new_values' => $device->fresh()->only(['active', 'user_id']),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        if (! $device->active) {
            $device->user?->tokens()->where('name', 'device:'.$device->device_uuid)->delete();
        }

        return back()->with('success', $device->active ? 'Dispositivo activado.' : 'Dispositivo desactivado y sesión revocada.');
    }
}
