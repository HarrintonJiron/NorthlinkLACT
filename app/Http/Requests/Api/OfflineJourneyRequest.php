<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OfflineJourneyRequest extends FormRequest
{
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
