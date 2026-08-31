<?php

namespace App\Modules\Personnel\Requests;

class StoreEmployeeRequest extends EmployeeRequest
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
        return $this->baseRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->baseMessages();
    }
}
