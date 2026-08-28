<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProducerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:20',
            'full_name' => 'required|string|max:255',
            'identity_number' => 'nullable|string|regex:/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'community' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'active' => 'boolean',
            'route_id' => 'nullable|exists:routes,id',
            'payment_method' => 'nullable|in:cash,transfer,check',
        ];
    }

    public function messages(): array
    {
        return [
            'identity_number.regex' => 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
            'payment_method.in' => 'El método de pago debe ser efectivo, transferencia o cheque',
        ];
    }
}
