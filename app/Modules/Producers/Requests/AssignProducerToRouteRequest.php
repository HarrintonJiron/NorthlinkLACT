<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignProducerToRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('assign_producers');
    }

    public function rules(): array
    {
        return [
            'producer_id' => 'required|exists:producers,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_at' => 'required|date',
        ];
    }
}
