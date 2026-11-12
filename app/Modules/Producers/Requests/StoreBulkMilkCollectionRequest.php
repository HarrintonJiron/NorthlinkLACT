<?php

namespace App\Modules\Producers\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBulkMilkCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create_collections');
    }

    public function rules(): array
    {
        return [
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'week_start' => ['required', 'date_format:Y-m-d'],
            'entries' => ['required', 'array', 'min:1', 'max:1000'],
            'entries.*.producer_id' => ['required', 'integer', 'exists:producers,id'],
            'entries.*.date' => ['required', 'date_format:Y-m-d'],
            'entries.*.liters' => ['required', 'decimal:0,2', 'gt:0', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $weekStart = CarbonImmutable::parse($this->string('week_start')->toString())->startOfWeek();
                $weekEnd = $weekStart->endOfWeek();
                $seen = [];

                foreach ($this->array('entries') as $index => $entry) {
                    $date = CarbonImmutable::parse($entry['date']);
                    $key = $entry['producer_id'].'|'.$date->toDateString();

                    if (! $date->betweenIncluded($weekStart, $weekEnd)) {
                        $validator->errors()->add("entries.{$index}.date", 'La fecha debe pertenecer a la semana seleccionada.');
                    }

                    if (isset($seen[$key])) {
                        $validator->errors()->add("entries.{$index}", 'Hay una celda repetida para el mismo productor y fecha.');
                    }

                    $seen[$key] = true;
                }
            },
        ];
    }
}
