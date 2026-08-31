<?php

namespace App\Modules\Finanzas\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceTransactionRequest extends StoreFinanceTransactionRequest
{
    public function rules(): array
    {
        $currentCategoryId = $this->route('transaction')?->category_id;

        return array_merge(parent::rules(), [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('finance_categories', 'id')->where(function ($query) use ($currentCategoryId) {
                    $query->where('active', true);
                    if ($currentCategoryId) {
                        $query->orWhere('id', $currentCategoryId);
                    }
                }),
            ],
        ]);
    }
}
