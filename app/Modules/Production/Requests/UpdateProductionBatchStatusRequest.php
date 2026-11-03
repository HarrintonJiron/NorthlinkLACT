<?php

namespace App\Modules\Production\Requests;

use App\Modules\Production\Models\ProductionBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductionBatchStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->is_admin || $this->user()?->hasPermission('access_production'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                ProductionBatch::STATUS_IN_PROCESS,
                ProductionBatch::STATUS_CLOSED,
                ProductionBatch::STATUS_RELEASED,
            ])],
            'cream_weight_lb' => ['nullable', 'numeric', 'min:0', 'max:99999999.999'],
            'trimmings_weight_lb' => ['nullable', 'numeric', 'min:0', 'max:99999999.999'],
            'reprocess_weight_lb' => ['nullable', 'numeric', 'min:0', 'max:99999999.999'],
            'waste_weight_lb' => ['nullable', 'numeric', 'min:0', 'max:99999999.999'],
        ];
    }
}
