<?php

namespace App\Modules\Admin\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique(User::class, 'username')->ignore($user),
            ],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'pin' => ['nullable', 'digits:4'],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.min' => 'El usuario debe tener al menos 3 caracteres.',
            'username.regex' => 'El usuario solo puede contener letras minúsculas, números, punto, guion o guion bajo.',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'pin.digits' => 'El PIN debe contener exactamente 4 dígitos.',
            'active.required' => 'El estado del usuario es obligatorio.',
        ];
    }
}
