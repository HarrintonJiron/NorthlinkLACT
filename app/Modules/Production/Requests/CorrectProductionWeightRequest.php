<?php

namespace App\Modules\Production\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectProductionWeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_admin || $this->user()?->hasPermission('access_production'));
    }

    public function rules(): array
    {
        return [
            'weight_lb' => ['required', 'numeric', 'gt:0', 'max:99999999.999'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
