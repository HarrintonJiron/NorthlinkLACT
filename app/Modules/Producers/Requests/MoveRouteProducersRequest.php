<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveRouteProducersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->active && $this->user()?->is_admin);
    }

    public function rules(): array
    {
        return [
            'target_route_id' => [
                'required',
                'integer',
                Rule::exists('routes', 'id')->where(fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('active', true)),
                Rule::notIn([(int) $this->route('route')?->id]),
            ],
            'producer_ids' => ['required', 'array', 'min:1'],
            'producer_ids.*' => ['required', 'integer', 'distinct', Rule::exists('producers', 'id')->whereNull('deleted_at')],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
