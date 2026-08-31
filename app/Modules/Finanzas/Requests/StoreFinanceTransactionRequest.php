<?php

namespace App\Modules\Finanzas\Requests;

use App\Modules\Finanzas\Models\FinanceTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullableStrings = ['description', 'payment_method', 'reference', 'payee', 'notes'];
        foreach ($nullableStrings as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->input('category_id') === '') {
            $this->merge(['category_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in([
                FinanceTransaction::TYPE_GASTO,
                FinanceTransaction::TYPE_PAGO,
                FinanceTransaction::TYPE_INGRESO,
            ])],
            'category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where('active', true)],
            'concept' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', Rule::in(['efectivo', 'transferencia', 'cheque', 'tarjeta', 'otro'])],
            'reference' => ['nullable', 'string', 'max:100'],
            'payee' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Selecciona el tipo de movimiento.',
            'type.in' => 'El tipo de movimiento no es válido.',
            'concept.required' => 'El concepto es obligatorio.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'transaction_date.required' => 'La fecha es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'payment_method.in' => 'El método de pago no es válido.',
        ];
    }
}
