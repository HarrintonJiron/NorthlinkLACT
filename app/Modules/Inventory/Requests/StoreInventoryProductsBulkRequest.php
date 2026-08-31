<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryProductsBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1', 'max:50'],
            'products.*.name' => ['required', 'string', 'max:150'],
            'products.*.description' => ['nullable', 'string', 'max:1000'],
            'products.*.unit_id' => ['required', 'integer', Rule::exists('inventory_units', 'id')->where('active', true)],
            'products.*.stock' => ['nullable', 'numeric', 'min:0'],
            'products.*.min_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'products.required' => 'Debes agregar al menos un producto.',
            'products.min' => 'Debes agregar al menos un producto.',
            'products.max' => 'Puedes cargar hasta 50 productos a la vez.',
            'products.*.name.required' => 'Cada producto debe tener nombre.',
            'products.*.unit_id.required' => 'Cada producto debe tener unidad de medida.',
            'products.*.unit_id.exists' => 'Hay una unidad de medida no válida.',
            'products.*.stock.min' => 'El stock no puede ser negativo.',
            'products.*.min_stock.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
