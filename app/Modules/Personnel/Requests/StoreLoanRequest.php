<?php

namespace App\Modules\Personnel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'installment_amount' => ['required', 'numeric', 'min:0.01', 'lte:amount'],
            'granted_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Ingresa el monto del préstamo.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'installment_amount.required' => 'Ingresa el monto de la cuota por planilla.',
            'installment_amount.lte' => 'La cuota no puede ser mayor al monto del préstamo.',
            'granted_at.required' => 'Ingresa la fecha de entrega del préstamo.',
        ];
    }
}
