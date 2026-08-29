<?php

namespace App\Modules\Ruteros\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRuteroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rutero = $this->route('rutero');

        return [
            'full_name' => 'required|string|max:255',
            'identity_number' => [
                'required',
                'string',
                'regex:/^\d{3}-\d{5}-\d{4}[A-Za-z]?$/',
                Rule::unique('ruteros', 'identity_number')->ignore($rutero),
            ],
            'phone' => 'required|string|max:20',
            'vehicle_plate' => 'required|string|max:20',
            'route_id' => [
                'required',
                Rule::exists('routes', 'id')->whereNull('deleted_at'),
                Rule::unique('ruteros', 'route_id')->ignore($rutero),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre del propietario es obligatorio.',
            'identity_number.required' => 'La cédula es obligatoria.',
            'identity_number.regex' => 'El formato de la cédula debe ser XXX-XXXXX-XXXX (ejemplo: 001-12345-0001A)',
            'identity_number.unique' => 'Ya existe un rutero con esa cédula.',
            'phone.required' => 'El número de teléfono es obligatorio.',
            'vehicle_plate.required' => 'La placa del vehículo es obligatoria.',
            'route_id.required' => 'El rutero debe tener una ruta asignada.',
            'route_id.unique' => 'Esa ruta ya tiene un rutero.',
        ];
    }
}
