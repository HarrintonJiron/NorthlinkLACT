<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRuteroToRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rutero_id' => [
                'required',
                Rule::exists('ruteros', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rutero_id.required' => 'Debes seleccionar un rutero.',
            'rutero_id.exists' => 'El rutero seleccionado no existe.',
        ];
    }
}
