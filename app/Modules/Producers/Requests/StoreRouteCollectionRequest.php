<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producer_id' => 'required|exists:producers,id',
            'collection_date' => 'required|date',
            'liters' => 'required|numeric|min:0|max:10000',
        ];
    }
}
