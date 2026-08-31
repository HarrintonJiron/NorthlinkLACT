<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('expiration_date') === '') {
            $this->merge(['expiration_date' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_id' => ['required', 'integer', Rule::exists('inventory_units', 'id')->where('active', true)],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'expiration_date' => ['nullable', 'date'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'unit_id.required' => 'Debes seleccionar una unidad de medida.',
            'unit_id.exists' => 'La unidad de medida seleccionada no es válida.',
            'stock.min' => 'El stock no puede ser negativo.',
            'min_stock.min' => 'El stock mínimo no puede ser negativo.',
            'expiration_date.date' => 'La fecha de vencimiento no es válida.',
        ];
    }
}
