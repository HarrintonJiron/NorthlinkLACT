<?php

namespace App\Modules\Admin\Requests;

use App\Models\User;
use App\Modules\Personnel\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_id' => $this->filled('employee_id') ? $this->integer('employee_id') : null,
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists(Employee::class, 'id')->where('status', 'activo'),
                Rule::unique(User::class, 'employee_id'),
            ],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique(User::class, 'username'),
            ],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'pin' => ['required', 'digits:4'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Selecciona un colaborador.',
            'employee_id.exists' => 'El colaborador seleccionado no está disponible.',
            'employee_id.unique' => 'Este colaborador ya tiene un usuario asignado.',
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.min' => 'El usuario debe tener al menos 3 caracteres.',
            'username.regex' => 'El usuario solo puede contener letras minúsculas, números, punto, guion o guion bajo.',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'pin.required' => 'El PIN es obligatorio.',
            'pin.digits' => 'El PIN debe contener exactamente 4 dígitos.',
        ];
    }
}
