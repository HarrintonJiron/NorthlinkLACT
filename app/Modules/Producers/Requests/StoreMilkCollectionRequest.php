<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilkCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_collections');
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'plant_id' => 'required|exists:plants,id',
            'route_id' => 'required|exists:routes,id',
            'producer_id' => 'required|exists:producers,id',
            'collection_date' => 'required|date',
            'liters' => 'required|numeric|min:0|max:10000',
            'temperature' => 'nullable|numeric|between:-10,50',
            'acidity' => 'nullable|numeric|between:0,20',
            'fat_percentage' => 'nullable|numeric|between:0,100',
            'notes' => 'nullable|string',
        ];
    }
}
