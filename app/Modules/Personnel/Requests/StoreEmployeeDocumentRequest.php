<?php

namespace App\Modules\Personnel\Requests;

use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'type' => $this->input('type'),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = array_column(app(PersonnelService::class)->documentTypeOptions(), 'value');

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in($types)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del documento es obligatorio.',
            'type.required' => 'Selecciona el tipo de documento.',
            'file.required' => 'Debes adjuntar un archivo.',
            'file.max' => 'El archivo no puede superar 10 MB.',
        ];
    }
}
