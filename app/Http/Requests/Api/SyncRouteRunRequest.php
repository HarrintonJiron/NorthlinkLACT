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
            'new_producers' => ['sometimes', 'array', 'max:100'],
            'new_producers.*.external_uuid' => ['required', 'uuid', 'distinct'],
            'new_producers.*.full_name' => ['required', 'string', 'max:255'],
            'new_producers.*.phone' => ['nullable', 'string', 'max:50'],
            'collections' => ['present', 'array', 'max:500'],
            'collections.*' => ['array'],
            'collections.*.uuid' => ['required', 'uuid', 'distinct'],
            'collections.*.producer_id' => ['nullable', 'integer', 'exists:producers,id', 'required_without:collections.*.producer_external_uuid'],
            'collections.*.producer_external_uuid' => ['nullable', 'uuid', 'required_without:collections.*.producer_id'],
            'collections.*.collection_date' => ['required', 'date_format:Y-m-d'],
            'collections.*.liters' => ['required', 'numeric', 'min:0', 'max:10000'],
            'collections.*.visit_status' => ['sometimes', Rule::in(['entregado', 'ceroconfirmado', 'ausente', 'rechazado'])],
            'collections.*.density' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'collections.*.original_printed_at' => ['nullable', 'date'],
            'collections.*.print_copy_count' => ['sometimes', 'integer', 'min:0', 'max:100'],
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
                    if (
                        ($collection['visit_status'] ?? 'entregado') === 'entregado'
                        && (float) ($collection['liters'] ?? 0) <= 0
                    ) {
                        $validator->errors()->add(
                            "collections.$index.liters",
                            'Una entrega debe registrar más de cero litros.',
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
