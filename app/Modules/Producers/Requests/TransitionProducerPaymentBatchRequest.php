<?php

namespace App\Modules\Producers\Requests;

use App\Modules\Producers\Models\ProducerPaymentBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionProducerPaymentBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_finances');
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    ProducerPaymentBatch::STATUS_REVIEWED,
                    ProducerPaymentBatch::STATUS_APPROVED,
                ]),
            ],
        ];
    }
}
