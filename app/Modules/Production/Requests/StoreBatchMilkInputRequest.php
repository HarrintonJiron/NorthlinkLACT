<?php

namespace App\Modules\Production\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchMilkInputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_admin || $this->user()?->hasPermission('access_production'));
    }

    public function rules(): array
    {
        return [
            'source_date' => ['required', 'date', 'before_or_equal:today'],
            'liters' => ['required', 'numeric', 'gt:0', 'max:99999999.999'],
        ];
    }
}
