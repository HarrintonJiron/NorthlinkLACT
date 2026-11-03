<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_admin || $this->user()?->hasPermission('access_routes'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la ruta es obligatorio.',
        ];
    }
}
