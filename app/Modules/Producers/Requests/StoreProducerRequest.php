<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProducerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_producers');
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:producers,code',
            'full_name' => 'required|string|max:255',
            'identity_number' => 'required|string|max:50|unique:producers,identity_number',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'community' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'active' => 'boolean',
        ];
    }
}
