<?php

namespace App\Http\Requests\Api;

use App\Models\RouteRun;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncRouteRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->active;
    }

    public function rules(): array
    {
        return [
            'device_uuid' => ['required', 'uuid'],
            'route_run' => ['required', 'array'],
            'route_run.uuid' => ['required', 'uuid'],
            'route_run.route_id' => ['required', 'integer', 'exists:routes,id'],
            'route_run.run_date' => ['required', 'date_format:Y-m-d'],
            'route_run.status' => [
                'required',
                Rule::in([
                    RouteRun::STATUS_IN_PROGRESS,
                    RouteRun::STATUS_COMPLETED,
                    RouteRun::STATUS_CANCELLED,
                ]),
            ],
            'route_run.started_at' => ['required', 'date'],
            'route_run.completed_at' => ['nullable', 'date', 'after_or_equal:route_run.started_at'],
            'collections' => ['present', 'array', 'max:500'],
            'collections.*' => ['array'],
            'collections.*.uuid' => ['required', 'uuid', 'distinct'],
            'collections.*.producer_id' => ['required', 'integer', 'exists:producers,id'],
            'collections.*.collection_date' => ['required', 'date_format:Y-m-d'],
            'collections.*.liters' => ['required', 'numeric', 'min:0.1', 'max:10000'],
            'collections.*.temperature' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'collections.*.acidity' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'collections.*.fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'collections.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $runDate = $this->input('route_run.run_date');

                foreach ($this->input('collections', []) as $index => $collection) {
                    if (($collection['collection_date'] ?? null) !== $runDate) {
                        $validator->errors()->add(
                            "collections.$index.collection_date",
                            'La fecha del acopio debe coincidir con la fecha de la ejecución de ruta.',
                        );
                    }
                }

                if (
                    $this->input('route_run.status') === RouteRun::STATUS_COMPLETED
                    && blank($this->input('route_run.completed_at'))
                ) {
                    $validator->errors()->add(
                        'route_run.completed_at',
                        'La fecha de finalización es obligatoria para una ruta completada.',
                    );
                }
            },
        ];
    }
}
