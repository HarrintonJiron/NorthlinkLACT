<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeSettlement;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id', function ($attribute, $value, $fail) {
                $employee = Employee::find($value);
                if ($employee && (float) $employee->base_salary <= 0) {
                    $fail('Este colaborador no tiene un sueldo base configurado; configúralo en su ficha antes de liquidarlo.');
                }
            }],
            'termination_type' => ['required', Rule::in([
                EmployeeSettlement::TYPE_UNJUSTIFIED_DISMISSAL,
                EmployeeSettlement::TYPE_RESIGNATION,
                EmployeeSettlement::TYPE_JUSTIFIED_DISMISSAL,
                EmployeeSettlement::TYPE_MUTUAL_AGREEMENT,
            ])],
            'termination_date' => ['required', 'date', function ($attribute, $value, $fail) {
                $employee = Employee::find($this->input('employee_id'));
                if ($employee && $employee->hired_at && Carbon::parse($value)->lessThan($employee->hired_at)) {
                    $fail('La fecha de salida no puede ser anterior a la fecha de contratación.');
                }
            }],
            'pending_salary_start' => ['required', 'date', 'before_or_equal:termination_date'],
            'severance_method' => ['required', Rule::in([
                EmployeeSettlement::SEVERANCE_METHOD_LEGAL,
                EmployeeSettlement::SEVERANCE_METHOD_MANUAL,
            ])],
            'severance_amount' => ['required_if:severance_method,manual', 'nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Selecciona el colaborador a liquidar.',
            'termination_type.required' => 'Selecciona el tipo de terminación.',
            'termination_date.required' => 'Ingresa la fecha de salida.',
            'pending_salary_start.required' => 'Ingresa desde cuándo se debe el salario pendiente.',
            'pending_salary_start.before_or_equal' => 'El inicio del salario pendiente no puede ser posterior a la fecha de salida.',
            'severance_amount.required_if' => 'Ingresa el monto de indemnización.',
        ];
    }
}
