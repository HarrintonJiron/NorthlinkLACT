<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_id' => $this->filled('employee_id') ? $this->integer('employee_id') : null,
            'attendance_date' => $this->input('attendance_date'),
            'type' => $this->input('type'),
            'check_in' => $this->filled('check_in') ? $this->input('check_in') : null,
            'check_out' => $this->filled('check_out') ? $this->input('check_out') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists(Employee::class, 'id')],
            'attendance_date' => ['required', 'date'],
            'type' => ['required', 'string', Rule::in(EmployeeAttendance::types())],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'justification' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('type');

            if (EmployeeAttendance::requiresCheckIn($type) && ! $this->filled('check_in')) {
                $validator->errors()->add('check_in', 'Indica la hora de entrada para este tipo de asistencia.');
            }

            if (EmployeeAttendance::requiresCheckOut($type) && ! $this->filled('check_out')) {
                $validator->errors()->add('check_out', 'Indica la hora de salida para este tipo de asistencia.');
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
            'attendance_date.required' => 'La fecha es obligatoria.',
            'type.required' => 'Selecciona el tipo de asistencia.',
            'type.in' => 'El tipo de asistencia seleccionado no es válido.',
            'check_out.after' => 'La hora de salida debe ser posterior a la entrada.',
            'justification.max' => 'El documento de justificación no puede superar 5 MB.',
        ];
    }
}
