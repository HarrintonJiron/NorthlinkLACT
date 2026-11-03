<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviseClosedPaymentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_finances');
    }

    public function rules(): array
    {
        return [
            'price_per_liter' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_per_liter.required' => 'El precio corregido es obligatorio.',
            'price_per_liter.gt' => 'El precio corregido debe ser mayor que cero.',
            'reason.required' => 'Debes explicar por qué se corrige una planilla cerrada.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
