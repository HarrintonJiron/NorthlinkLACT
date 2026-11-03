<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMilkCollectionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->active;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'route_id' => ['nullable', 'integer', 'min:1'],
            'visit_status' => ['nullable', Rule::in(['entregado', 'ausente', 'rechazado', 'pendiente'])],
            'sync_status' => ['nullable', Rule::in(['pending', 'synced', 'conflict'])],
            'source' => ['nullable', Rule::in(['offline', 'manual'])],
        ];
    }
}
