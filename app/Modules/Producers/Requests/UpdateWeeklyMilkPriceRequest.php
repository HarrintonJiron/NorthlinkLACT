<?php

namespace App\Modules\Producers\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWeeklyMilkPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_finances');
    }

    public function rules(): array
    {
        return [
            'week_end' => ['required', 'date'],
            'price_per_liter' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('week_end')) {
                    return;
                }

                try {
                    $isThursday = Carbon::parse($this->string('week_end')->toString())->isThursday();
                } catch (\Throwable) {
                    return;
                }

                if (! $isThursday) {
                    $validator->errors()->add('week_end', 'La planilla debe terminar un jueves.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'price_per_liter.required' => 'El precio por litro es obligatorio.',
            'price_per_liter.gt' => 'El precio por litro debe ser mayor que cero.',
            'reason.required' => 'Indica el motivo o autorización del cambio de precio.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
