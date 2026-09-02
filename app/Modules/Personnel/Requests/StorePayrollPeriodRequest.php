<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\PayrollPeriod;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'confirm_duplicate' => $this->boolean('confirm_duplicate'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pay_frequency' => ['required', Rule::in([
                Employee::FREQ_WEEKLY,
                Employee::FREQ_BIWEEKLY,
                Employee::FREQ_MONTHLY,
            ])],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'confirm_duplicate' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * No se bloquea generar una planilla en fechas que se traslapen con otra —
     * a veces son planillas separadas a propósito (por ejemplo, un grupo
     * distinto de colaboradores) — pero si algún colaborador ya tiene una
     * línea de planilla en ese rango, se advierte con sus nombres antes de
     * continuar, para que el pago doble sea una decisión consciente y no un
     * accidente. El frontend reenvía con confirm_duplicate=true para seguir
     * adelante después de ver la advertencia.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('period_start') || ! $this->filled('period_end') || $this->boolean('confirm_duplicate')) {
                return;
            }

            $overlappingPeriods = PayrollPeriod::query()
                ->where('period_start', '<=', $this->input('period_end'))
                ->where('period_end', '>=', $this->input('period_start'))
                ->with('items.employee:id,full_name')
                ->get();

            if ($overlappingPeriods->isEmpty()) {
                return;
            }

            $employeeNames = $overlappingPeriods
                ->flatMap(fn (PayrollPeriod $period) => $period->items)
                ->pluck('employee.full_name')
                ->filter()
                ->unique()
                ->sort()
                ->values();

            $periodCodes = $overlappingPeriods->pluck('code')->join(', ');
            $names = $employeeNames->take(8)->join(', ').($employeeNames->count() > 8 ? '…' : '');

            $message = $employeeNames->isEmpty()
                ? "Ya existe la planilla {$periodCodes} con fechas dentro de este rango, aunque todavía no tiene colaboradores. ¿Quieres generar esta planilla de todos modos?"
                : "La planilla {$periodCodes} ya cubre fechas dentro de este rango y ya incluye a: {$names}. Si generas esta planilla, esos colaboradores quedarían pagados dos veces para los días que se traslapan. ¿Quieres continuar de todos modos (por ejemplo, para una planilla separada)?";

            $validator->errors()->add('period_overlap', $message);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pay_frequency.required' => 'Selecciona la frecuencia de pago.',
            'pay_frequency.in' => 'La frecuencia de pago no es válida.',
            'period_start.required' => 'Ingresa la fecha de inicio del periodo.',
            'period_end.required' => 'Ingresa la fecha de fin del periodo.',
            'period_end.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la de inicio.',
        ];
    }
}
