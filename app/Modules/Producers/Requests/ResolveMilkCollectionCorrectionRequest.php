<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveMilkCollectionCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user?->active && ($user->is_admin || $user->hasPermission('access_collections')));
    }

    public function rules(): array
    {
        return [
            'liters' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'density' => ['nullable', 'numeric', 'between:0,99.999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'resolution_notes' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'liters.gt' => 'Los litros corregidos deben ser mayores que cero.',
            'resolution_notes.required' => 'Explique qué se corrigió para conservar la trazabilidad.',
            'resolution_notes.min' => 'La explicación de la corrección debe tener al menos 5 caracteres.',
        ];
    }
}
