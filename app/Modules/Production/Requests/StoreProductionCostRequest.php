<?php

namespace App\Modules\Production\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductionCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_production');
    }

    public function rules(): array
    {
        return ['category' => ['required', Rule::in(['milk', 'labor', 'supplies', 'energy', 'transport', 'other'])], 'description' => ['required', 'string', 'max:200'], 'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99']];
    }
}
