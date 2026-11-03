<?php

namespace App\Modules\Personnel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'inss_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'inss_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'inatec_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'ir_threshold_1' => ['required', 'numeric', 'min:0'],
            'ir_threshold_2' => ['required', 'numeric', 'gt:ir_threshold_1'],
            'ir_threshold_3' => ['required', 'numeric', 'gt:ir_threshold_2'],
            'ir_threshold_4' => ['required', 'numeric', 'gt:ir_threshold_3'],
            'ir_rate_1' => ['required', 'numeric', 'min:0', 'max:100'],
            'ir_rate_2' => ['required', 'numeric', 'min:0', 'max:100'],
            'ir_rate_3' => ['required', 'numeric', 'min:0', 'max:100'],
            'ir_rate_4' => ['required', 'numeric', 'min:0', 'max:100'],
            'ir_rate_5' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Ingresa un nombre para esta política.',
            'effective_from.required' => 'Ingresa la fecha desde la que aplica.',
            'ir_threshold_2.gt' => 'Cada tramo del IR debe ser mayor que el anterior.',
            'ir_threshold_3.gt' => 'Cada tramo del IR debe ser mayor que el anterior.',
            'ir_threshold_4.gt' => 'Cada tramo del IR debe ser mayor que el anterior.',
        ];
    }

    /**
     * @return array<int, array{threshold: float|null, rate: float}>
     */
    public function irBrackets(): array
    {
        $validated = $this->validated();

        return [
            ['threshold' => (float) $validated['ir_threshold_1'], 'rate' => (float) $validated['ir_rate_1'] / 100],
            ['threshold' => (float) $validated['ir_threshold_2'], 'rate' => (float) $validated['ir_rate_2'] / 100],
            ['threshold' => (float) $validated['ir_threshold_3'], 'rate' => (float) $validated['ir_rate_3'] / 100],
            ['threshold' => (float) $validated['ir_threshold_4'], 'rate' => (float) $validated['ir_rate_4'] / 100],
            ['threshold' => null, 'rate' => (float) $validated['ir_rate_5'] / 100],
        ];
    }
}
