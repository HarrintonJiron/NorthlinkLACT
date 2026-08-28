<?php

namespace App\Modules\Producers\Models;

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
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'liters' => 'decimal:2',
            'temperature' => 'decimal:2',
            'acidity' => 'decimal:2',
            'fat_percentage' => 'decimal:2',
        ];
    }

    public function company()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Company::class);
    }

    public function plant()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Plant::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function collectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
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
