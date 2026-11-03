<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Models\AguinaldoPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAguinaldoPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => [
                'required',
                'integer',
                'min:2020',
                'max:'.(now()->year + 1),
                Rule::unique(AguinaldoPeriod::class, 'year'),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => 'Selecciona el año del aguinaldo.',
            'year.unique' => 'Ya existe un aguinaldo generado para ese año.',
        ];
    }
}
