<?php

namespace App\Http\Requests\Api;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OfflineJourneyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('device_uuid')) {
            return;
        }

        $device = $this->attributes->get('offline_device');

        if ($device instanceof Device) {
            $this->merge(['device_uuid' => $device->device_uuid]);
        }
    }

    public function authorize(): bool
    {
        return (bool) $this->user()?->active;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'device_uuid' => ['required', 'uuid'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (now()->startOfDay()->diffInDays($this->date('date'), false) > 7) {
                    $validator->errors()->add('date', 'Solo se pueden preparar jornadas de los próximos siete días.');
                }
            },
        ];
    }
}
