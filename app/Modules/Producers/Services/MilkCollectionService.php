<?php

namespace App\Modules\Producers\Services;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Requests\StoreMilkCollectionRequest;
use Illuminate\Support\Facades\DB;
use App\Modules\Audit\Models\AuditEvent;

class MilkCollectionService
{
    public function create(StoreMilkCollectionRequest $request): MilkCollection
    {
        return DB::transaction(function () use ($request) {
            $collection = MilkCollection::create([
                ...$request->validated(),
                'collected_by' => $request->user()->id,
            ]);

            $this->auditCreate($collection, $request->user());

            return $collection;
        });
    }

    public function createWithLock(StoreMilkCollectionRequest $request): MilkCollection
    {
        return DB::transaction(function () use ($request) {
            $lock = DB::table('milk_collections')
                ->where('company_id', $request->company_id)
                ->where('plant_id', $request->plant_id)
                ->where('route_id', $request->route_id)
                ->where('producer_id', $request->producer_id)
                ->where('collection_date', $request->collection_date)
                ->lockForUpdate()
                ->first();

            if ($lock) {
                throw new \Exception('Ya existe un registro de acopio para este productor en esta fecha.');
            }

            return $this->create($request);
        });
    }

    protected function auditCreate(MilkCollection $collection, $user): void
    {
        AuditEvent::create([
            'user_id' => $user->id,
            'company_id' => $collection->company_id,
            'plant_id' => $collection->plant_id,
            'entity_type' => MilkCollection::class,
            'entity_id' => $collection->id,
            'action' => 'create',
            'description' => 'Registro de acopio de leche',
            'new_values' => $collection->getAttributes(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
