<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEmployeeRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'description' => $this->filled('description')
                ? Str::squish((string) $this->input('description'))
                : null,
            'active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(EmployeeRole::class, 'name')],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol con este nombre.',
            'description.max' => 'La descripción no puede exceder 255 caracteres.',
        ];
    }
}
