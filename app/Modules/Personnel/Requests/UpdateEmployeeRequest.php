<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim((string) $this->input('full_name')),
            'identity_number' => $this->filled('identity_number') ? trim((string) $this->input('identity_number')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'hired_at' => $this->filled('hired_at') ? $this->input('hired_at') : null,
            'employee_role_id' => $this->filled('employee_role_id') ? $this->integer('employee_role_id') : null,
            'active' => $this->boolean('active'),
            'base_salary' => $this->filled('base_salary') ? $this->input('base_salary') : null,
            'pay_frequency' => $this->filled('pay_frequency') ? $this->input('pay_frequency') : Employee::FREQ_MONTHLY,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'employee_role_id' => [
                'required',
                'integer',
                Rule::exists(EmployeeRole::class, 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('active', true)
                        ->orWhere('id', $employee->employee_role_id),
                ),
            ],
            'identity_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(Employee::class, 'identity_number')->ignore($employee),
            ],
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(Employee::class, 'email')->ignore($employee),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'hired_at' => ['nullable', 'date', 'before_or_equal:today'],
            'active' => ['required', 'boolean'],
            'base_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'pay_frequency' => ['required', Rule::in([
                Employee::FREQ_WEEKLY,
                Employee::FREQ_BIWEEKLY,
                Employee::FREQ_MONTHLY,
            ])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre completo es obligatorio.',
            'employee_role_id.required' => 'Selecciona un rol para el colaborador.',
            'employee_role_id.exists' => 'El rol seleccionado no está disponible.',
            'identity_number.unique' => 'Ya existe un colaborador con esta identificación.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un colaborador con este correo electrónico.',
            'hired_at.date' => 'Ingresa una fecha de ingreso válida.',
            'hired_at.before_or_equal' => 'La fecha de ingreso no puede estar en el futuro.',
            'base_salary.numeric' => 'El sueldo debe ser un número válido.',
            'base_salary.min' => 'El sueldo no puede ser negativo.',
            'pay_frequency.required' => 'Selecciona la frecuencia de pago.',
            'pay_frequency.in' => 'La frecuencia de pago no es válida.',
        ];
    }
}
