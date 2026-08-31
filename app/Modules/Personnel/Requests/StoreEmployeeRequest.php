<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'employee_role_id' => [
                'required',
                'integer',
                Rule::exists(EmployeeRole::class, 'id')->where('active', true),
            ],
            'identity_number' => ['nullable', 'string', 'max:50', Rule::unique(Employee::class, 'identity_number')],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(Employee::class, 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'hired_at' => ['nullable', 'date', 'before_or_equal:today'],
            'active' => ['required', 'boolean'],
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
        ];
    }
}
