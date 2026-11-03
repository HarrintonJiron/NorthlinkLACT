<?php

namespace App\Modules\Production\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinishedProductLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_production');
    }

    public function rules(): array
    {
        return ['inventory_product_id' => ['required', Rule::exists('inventory_products', 'id')->where('active', true)], 'quantity' => ['required', 'numeric', 'gt:0'], 'expiration_date' => ['nullable', 'date', 'after_or_equal:today']];
    }
}
