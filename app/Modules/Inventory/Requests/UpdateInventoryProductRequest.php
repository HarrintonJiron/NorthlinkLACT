<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_inventory');
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('expiration_date') === '') {
            $this->merge(['expiration_date' => null]);
        }
    }

    public function rules(): array
    {
        $currentUnitId = $this->route('product')?->unit_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('inventory_units', 'id')->where(function ($query) use ($currentUnitId) {
                    $query->where('active', true);
                    if ($currentUnitId) {
                        $query->orWhere('id', $currentUnitId);
                    }
                }),
            ],
            'stock' => ['nullable', 'numeric', 'min:0'],
            'adjustment_reason' => [
                Rule::requiredIf(function (): bool {
                    $current = (float) ($this->route('product')?->stock ?? 0);
                    $requested = (float) $this->input('stock', $current);

                    return abs($requested - $current) > 0.0005;
                }),
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
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
            'adjustment_reason.required' => 'Debes indicar el motivo del ajuste de inventario.',
            'adjustment_reason.min' => 'El motivo del ajuste debe tener al menos 5 caracteres.',
            'min_stock.min' => 'El stock mínimo no puede ser negativo.',
            'expiration_date.date' => 'La fecha de vencimiento no es válida.',
        ];
    }
}
