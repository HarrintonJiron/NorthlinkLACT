<?php

namespace App\Modules\Ruteros\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRuteroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_name' => 'required|string|max:255',
            'owner_identity_number' => [
                'required',
                'string',
                'regex:/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/',
                Rule::unique('ruteros', 'owner_identity_number'),
            ],
            'owner_phone' => 'required|string|max:20',
            'vehicle_description' => 'required|string|max:255',
            'vehicle_plate' => 'required|string|max:20',
            'driver_name' => 'required|string|max:255',
            'driver_identity_number' => [
                'required',
                'string',
                'regex:/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/',
            ],
            'driver_phone' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required' => 'El nombre del propietario es obligatorio.',
            'owner_identity_number.required' => 'La cédula del propietario es obligatoria.',
            'owner_identity_number.regex' => 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)',
            'owner_identity_number.unique' => 'Ya existe un rutero con esa cédula de propietario.',
            'owner_phone.required' => 'El teléfono del propietario es obligatorio.',
            'vehicle_description.required' => 'El vehículo es obligatorio.',
            'vehicle_plate.required' => 'La placa del vehículo es obligatoria.',
            'driver_name.required' => 'El nombre del encargado es obligatorio.',
            'driver_identity_number.required' => 'La cédula del encargado es obligatoria.',
            'driver_identity_number.regex' => 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)',
            'driver_phone.required' => 'El teléfono del encargado es obligatorio.',
        ];
    }
}
