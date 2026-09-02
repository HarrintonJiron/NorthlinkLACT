<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\EmployeeVacation;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacationRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'paid' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('start_date') || ! $this->filled('end_date') || ! $this->boolean('paid')) {
                return;
            }

            $employee = $this->route('employee');
            if (! $employee) {
                return;
            }

            $days = Carbon::parse($this->input('start_date'))->diffInDays(Carbon::parse($this->input('end_date'))) + 1;

            // Al editar una solicitud ya aprobada, sus días propios ya están contados
            // como "tomados" en el saldo actual; hay que devolverlos al saldo antes de
            // comparar, para no bloquear una edición que en realidad no pide más días.
            $existingVacation = $this->route('vacation');
            $alreadyCountedDays = ($existingVacation instanceof EmployeeVacation && $existingVacation->status === EmployeeVacation::STATUS_APPROVED)
                ? (float) $existingVacation->days
                : 0.0;

            $availableBalance = $employee->vacationBalance() + $alreadyCountedDays;

            if ($days > $availableBalance) {
                $validator->errors()->add(
                    'start_date',
                    "Este colaborador solo tiene {$availableBalance} día(s) de vacaciones disponibles; esta solicitud pide {$days}."
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Ingresa la fecha de inicio de las vacaciones.',
            'end_date.required' => 'Ingresa la fecha de fin de las vacaciones.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la de inicio.',
        ];
    }
}
