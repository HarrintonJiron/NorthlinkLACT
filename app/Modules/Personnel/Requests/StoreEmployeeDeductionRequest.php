<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_id' => $this->filled('employee_id') ? $this->integer('employee_id') : null,
            'type' => $this->input('type'),
            'amount' => $this->filled('amount') ? $this->input('amount') : null,
            'total_amount' => $this->filled('total_amount') ? $this->input('total_amount') : null,
            'installment_amount' => $this->filled('installment_amount') ? $this->input('installment_amount') : null,
            'installments_total' => $this->filled('installments_total') ? $this->integer('installments_total') : null,
            'deduction_date' => $this->input('deduction_date'),
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : null,
            'status' => $this->input('status', PersonnelService::DEDUCTION_STATUS_ACTIVA),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_column(app(PersonnelService::class)->deductionTypeOptions(), 'value');
        $statuses = array_column(app(PersonnelService::class)->deductionStatusOptions(), 'value');

        return [
            'employee_id' => ['required', 'integer', Rule::exists(Employee::class, 'id')],
            'type' => ['required', 'string', Rule::in($types)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'total_amount' => ['nullable', 'numeric', 'min:0.01'],
            'installment_amount' => ['nullable', 'numeric', 'min:0.01'],
            'installments_total' => ['nullable', 'integer', 'min:1', 'max:120'],
            'deduction_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', Rule::in($statuses)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('type');

            if ($type === PersonnelService::DEDUCTION_INSS) {
                $validator->errors()->add('type', 'El INSS se calcula automáticamente al marcar al colaborador como asegurado.');

                return;
            }

            $typesWithInstallments = app(PersonnelService::class)->deductionTypesWithInstallments();

            if (! in_array($type, $typesWithInstallments, true)) {
                return;
            }

            if (! $this->filled('total_amount')) {
                $validator->errors()->add('total_amount', 'Indica el monto total del adelanto o préstamo.');
            }

            if (! $this->filled('installment_amount')) {
                $validator->errors()->add('installment_amount', 'Indica el monto de la cuota.');
            }

            if (! $this->filled('installments_total')) {
                $validator->errors()->add('installments_total', 'Indica el número de cuotas.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Selecciona un colaborador.',
            'type.required' => 'Selecciona el tipo de deducción.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'deduction_date.required' => 'La fecha es obligatoria.',
        ];
    }
}
