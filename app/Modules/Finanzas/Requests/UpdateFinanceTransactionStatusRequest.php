<?php

namespace App\Modules\Finanzas\Requests;

use App\Modules\Finanzas\Models\FinanceTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceTransactionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('access_finances');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(FinanceTransaction::STATUSES)],
            'reason' => [
                Rule::requiredIf($this->input('status') === FinanceTransaction::STATUS_VOIDED),
                'nullable',
                'string',
                'min:8',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Debes explicar por qué se anula el movimiento.',
            'reason.min' => 'El motivo de anulación debe tener al menos 8 caracteres.',
        ];
    }
}
