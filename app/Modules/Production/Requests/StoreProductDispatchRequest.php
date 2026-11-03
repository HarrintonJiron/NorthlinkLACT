<?php

namespace App\Modules\Production\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_production');
    }

    protected function prepareForValidation(): void
    {
        if (is_array($this->items)) {
            $this->merge(['items' => collect($this->items)->filter(fn ($item) => (float) ($item['quantity'] ?? 0) > 0)->values()->all()]);
        }
    }

    public function rules(): array
    {
        return ['dispatch_date' => ['required', 'date'], 'destination' => ['required', 'string', 'max:200'], 'recipient' => ['nullable', 'string', 'max:150'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.lot_id' => ['required', 'distinct', Rule::exists('finished_product_lots', 'id')], 'items.*.quantity' => ['required', 'numeric', 'gt:0']];
    }
}
