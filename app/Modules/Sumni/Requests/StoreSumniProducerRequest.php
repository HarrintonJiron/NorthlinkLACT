<?php

namespace App\Modules\Sumni\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSumniProducerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'identity_number' => [
                'nullable',
                'string',
                'regex:/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/',
                Rule::unique('producers', 'identity_number'),
            ],
            'phone' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre del cliente es obligatorio.',
            'identity_number.regex' => 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)',
            'identity_number.unique' => 'Ya existe un productor con esa cédula.',
            'phone.required' => 'El teléfono es obligatorio.',
        ];
    }
}
