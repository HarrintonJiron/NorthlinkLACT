<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_routes');
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'plant_id' => 'required|exists:plants,id',
            'code' => 'required|string|max:50|unique:routes,code,NULL,id,company_id,' . $this->company_id . ',plant_id,' . $this->plant_id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ];
    }
}
