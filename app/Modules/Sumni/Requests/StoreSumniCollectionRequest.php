<?php

namespace App\Modules\Sumni\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSumniCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producer_id' => [
                'required',
                Rule::exists('producers', 'id')->where(fn ($query) => $query
                    ->where('active', true)
                    ->whereNull('deleted_at')),
            ],
            'liters' => 'required|numeric|min:0.1|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'producer_id.required' => 'Selecciona un cliente',
            'producer_id.exists' => 'El cliente no está activo o no existe.',
            'liters.required' => 'Digita los litros recolectados',
            'liters.min' => 'Los litros deben ser mayores a 0',
        ];
    }
}
