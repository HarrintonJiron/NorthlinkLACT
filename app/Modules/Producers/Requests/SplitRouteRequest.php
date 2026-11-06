<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SplitRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->active && $this->user()?->is_admin);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('routes', 'name')->whereNull('deleted_at')],
            'producer_ids' => ['required', 'array', 'min:1'],
            'producer_ids.*' => ['required', 'integer', 'distinct', Rule::exists('producers', 'id')->whereNull('deleted_at')],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
