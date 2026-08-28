<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilkPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_prices');
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'plant_id' => 'nullable|exists:plants,id',
            'price_per_liter' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_until' => 'nullable|date|after:effective_from',
            'active' => 'boolean',
        ];
    }
}
