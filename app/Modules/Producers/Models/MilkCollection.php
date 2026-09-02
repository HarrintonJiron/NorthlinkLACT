<?php

namespace App\Modules\Producers\Models;

use App\Models\Device;
use App\Models\RouteRun;
use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use Illuminate\Database\Eloquent\Model;

class MilkCollection extends Model
{
    protected $fillable = [
        'company_id',
        'plant_id',
        'route_id',
        'producer_id',
        'collection_date',
        'liters',
        'temperature',
        'acidity',
        'fat_percentage',
        'collected_by',
        'verified_by',
        'notes',
        'external_uuid',
        'route_run_id',
        'device_id',
        'sync_status',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'liters' => 'decimal:2',
            'temperature' => 'decimal:2',
            'acidity' => 'decimal:2',
            'fat_percentage' => 'decimal:2',
            'synced_at' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function routeRun()
    {
        return $this->belongsTo(RouteRun::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForRouteAndDate($query, $routeId, $date)
    {
        return $query->where('route_id', $routeId)
            ->where('collection_date', $date);
    }

    public function scopeForProducerAndDate($query, $producerId, $date)
    {
        return $query->where('producer_id', $producerId)
            ->where('collection_date', $date);
    }
}
