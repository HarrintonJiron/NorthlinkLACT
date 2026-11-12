<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProducerWeekAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week_end' => 'required|date',
            'preferred_price_per_liter' => 'nullable|numeric|min:0|max:10000',
            'density_price' => 'nullable|numeric|min:0|max:10000',
            'advance_amount' => 'nullable|numeric|min:0|max:1000000',
            'notes' => 'nullable|string|max:1000',
            'return_to' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'week_end.required' => 'La semana es obligatoria.',
            'preferred_price_per_liter.min' => 'El precio personalizado no puede ser negativo.',
            'density_price.min' => 'El precio por densidad no puede ser negativo.',
            'advance_amount.min' => 'El adelanto no puede ser negativo.',
        ];
    }
}
