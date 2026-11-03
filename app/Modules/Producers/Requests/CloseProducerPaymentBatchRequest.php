<?php

namespace App\Modules\Producers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseProducerPaymentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_finances');
    }

    public function rules(): array
    {
        return ['week_end' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
