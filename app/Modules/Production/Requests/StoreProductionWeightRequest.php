<?php

namespace App\Modules\Production\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionWeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_admin || $this->user()?->hasPermission('access_production'));
    }

    public function rules(): array
    {
        return [
            'weight_lb' => ['required', 'numeric', 'gt:0', 'max:99999999.999'],
            'scale_reference' => ['nullable', 'string', 'max:100'],
            'weighed_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }
}
