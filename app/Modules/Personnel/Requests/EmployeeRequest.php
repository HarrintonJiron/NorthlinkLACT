<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Admin\Models\Plant;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class EmployeeRequest extends FormRequest
{
    protected function personnelService(): PersonnelService
    {
        return app(PersonnelService::class);
    }

    /**
     * @return array<int, string>
     */
    protected function statusValues(): array
    {
        return array_column($this->personnelService()->statusOptions(), 'value');
    }

    /**
     * @return array<int, string>
     */
    protected function contractTypeValues(): array
    {
        return array_column($this->personnelService()->contractTypeOptions(), 'value');
    }

    /**
     * @return array<int, string>
     */
    protected function paymentMethodValues(): array
    {
        return array_column($this->personnelService()->paymentMethodOptions(), 'value');
    }

    /**
     * @return array<int, string>
     */
    protected function areaValues(): array
    {
        return array_column($this->personnelService()->areaOptions(), 'value');
    }

    protected function prepareEmployeeFields(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'identity_number' => $this->filled('identity_number') ? trim((string) $this->input('identity_number')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'area' => $this->filled('area') ? trim((string) $this->input('area')) : null,
            'hired_at' => $this->filled('hired_at') ? $this->input('hired_at') : null,
            'employee_role_id' => $this->filled('employee_role_id') ? $this->integer('employee_role_id') : null,
            'plant_id' => $this->filled('plant_id') ? $this->integer('plant_id') : null,
            'status' => $this->input('status', PersonnelService::STATUS_ACTIVO),
            'contract_type' => $this->filled('contract_type') ? $this->input('contract_type') : null,
            'contract_start_date' => $this->filled('contract_start_date') ? $this->input('contract_start_date') : null,
            'contract_end_date' => $this->filled('contract_end_date') ? $this->input('contract_end_date') : null,
            'salary' => $this->filled('salary') ? $this->input('salary') : null,
            'inss_insured' => $this->boolean('inss_insured'),
            'inss_number' => $this->filled('inss_number') ? trim((string) $this->input('inss_number')) : null,
            'payment_method' => $this->filled('payment_method') ? $this->input('payment_method') : null,
            'bank_account' => $this->filled('bank_account') ? trim((string) $this->input('bank_account')) : null,
            'emergency_contact_name' => $this->filled('emergency_contact_name') ? trim((string) $this->input('emergency_contact_name')) : null,
            'emergency_contact_phone' => $this->filled('emergency_contact_phone') ? trim((string) $this->input('emergency_contact_phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseRules(?Employee $employee = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'employee_role_id' => [
                'required',
                'integer',
                Rule::exists(EmployeeRole::class, 'id')->where(
                    fn ($query) => $employee
                        ? $query->where('active', true)->orWhere('id', $employee->employee_role_id)
                        : $query->where('active', true),
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
            'address' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:100', Rule::in($this->areaValues())],
            'plant_id' => ['nullable', 'integer', Rule::exists(Plant::class, 'id')],
            'hired_at' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['required', 'string', Rule::in($this->statusValues())],
            'contract_type' => ['nullable', 'string', Rule::in($this->contractTypeValues())],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'inss_insured' => ['required', 'boolean'],
            'inss_number' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', Rule::in($this->paymentMethodValues())],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function baseMessages(): array
    {
        return [
            'first_name.required' => 'Los nombres son obligatorios.',
            'last_name.required' => 'Los apellidos son obligatorios.',
            'employee_role_id.required' => 'Selecciona un cargo para el colaborador.',
            'employee_role_id.exists' => 'El cargo seleccionado no está disponible.',
            'identity_number.unique' => 'Ya existe un colaborador con esta cédula.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Ya existe un colaborador con este correo electrónico.',
            'hired_at.before_or_equal' => 'La fecha de ingreso no puede estar en el futuro.',
            'contract_end_date.after_or_equal' => 'La fecha de finalización debe ser posterior al inicio del contrato.',
            'salary.min' => 'El salario no puede ser negativo.',
        ];
    }
}
