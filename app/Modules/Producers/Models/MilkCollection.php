<?php

namespace App\Modules\Producers\Models;

use App\Models\Device;
use App\Models\RouteRun;
use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use Database\Factories\Modules\Producers\MilkCollectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilkCollection extends Model
{
    use HasFactory;

    public const CORRECTION_REQUESTED = 'requested';

    public const CORRECTION_RESOLVED = 'resolved';

    protected static function newFactory(): MilkCollectionFactory
    {
        return MilkCollectionFactory::new();
    }

    protected $fillable = [
        'company_id',
        'plant_id',
        'route_id',
        'producer_id',
        'collection_date',
        'liters',
        'visit_status',
        'density',
        'original_printed_at',
        'print_copy_count',
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
        'correction_status',
        'correction_reason',
        'correction_requested_at',
        'correction_requested_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'liters' => 'decimal:2',
            'density' => 'decimal:3',
            'original_printed_at' => 'datetime',
            'print_copy_count' => 'integer',
            'temperature' => 'decimal:2',
            'acidity' => 'decimal:2',
            'fat_percentage' => 'decimal:2',
            'synced_at' => 'datetime',
            'correction_requested_at' => 'datetime',
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

    public function correctionRequestedBy()
    {
        return $this->belongsTo(User::class, 'correction_requested_by');
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
