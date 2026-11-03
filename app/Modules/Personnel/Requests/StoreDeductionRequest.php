<?php

namespace App\Modules\Personnel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'concept' => trim((string) $this->input('concept')),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'deduction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'concept.required' => 'Ingresa el concepto de la deducción.',
            'amount.required' => 'Ingresa el monto de la deducción.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'deduction_date.required' => 'Ingresa la fecha de la deducción.',
        ];
    }
}
