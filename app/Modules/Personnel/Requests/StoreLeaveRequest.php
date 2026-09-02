<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\EmployeeLeave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'paid' => $this->boolean('paid'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                EmployeeLeave::TYPE_SICK,
                EmployeeLeave::TYPE_MATERNITY,
                EmployeeLeave::TYPE_PATERNITY,
                EmployeeLeave::TYPE_BEREAVEMENT,
                EmployeeLeave::TYPE_MARRIAGE,
                EmployeeLeave::TYPE_LEGAL,
                EmployeeLeave::TYPE_UNPAID_PERSONAL,
                EmployeeLeave::TYPE_OTHER,
            ])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'paid' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Selecciona el tipo de permiso.',
            'type.in' => 'El tipo de permiso no es válido.',
            'start_date.required' => 'Ingresa la fecha de inicio del permiso.',
            'end_date.required' => 'Ingresa la fecha de fin del permiso.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la de inicio.',
        ];
    }
}
