<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProducerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->active && ($user->is_admin || $user->hasPermission('access_producers')));
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:20', Rule::unique('producers', 'code')],
            'full_name' => 'required|string|max:255',
            'identity_number' => [
                'nullable',
                'string',
                'regex:/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/',
                Rule::unique('producers', 'identity_number'),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'community' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'active' => 'boolean',
            'route_id' => ['required', Rule::exists('routes', 'id')->whereNull('deleted_at')],
            'payment_method' => 'nullable|in:cash,transfer,check',
            'bank_name' => 'nullable|required_if:payment_method,transfer|string|max:255',
            'bank_account_number' => 'nullable|required_if:payment_method,transfer|string|max:100',
            'bank_account_holder' => 'nullable|required_if:payment_method,transfer|string|max:255',
            'preferred_price_per_liter' => 'nullable|numeric|min:0|max:10000',
            'payment_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'identity_number.regex' => 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)',
            'identity_number.unique' => 'Ya existe un productor con esa cédula.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
            'route_id.required' => 'Cada cliente debe pertenecer a una ruta',
            'payment_method.in' => 'El método de pago debe ser efectivo, transferencia o cheque',
            'bank_account_number.required_if' => 'La cuenta bancaria es obligatoria cuando el pago es por transferencia.',
        ];
    }
}
