<?php

namespace App\Modules\Sumni\Requests;

use App\Http\Requests\Api\SyncRouteRunRequest;

class SyncSumniOfflineRequest extends SyncRouteRunRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->active && $user->hasPermission('access_sumni'));
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'device_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ];
    }
}
