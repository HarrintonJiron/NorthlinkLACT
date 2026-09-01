<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;

class UpdateEmployeeRequest extends EmployeeRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareEmployeeFields();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return $this->baseRules($employee);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->baseMessages();
    }
}
