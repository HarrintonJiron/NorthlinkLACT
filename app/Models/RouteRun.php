<?php

namespace App\Models;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Route;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'external_uuid',
    'route_id',
    'user_id',
    'device_id',
    'run_date',
    'status',
    'sync_status',
    'started_at',
    'completed_at',
    'synced_at',
])]
class RouteRun extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const SYNC_PENDING = 'pending';

    public const SYNC_SYNCED = 'synced';

    public const SYNC_CONFLICT = 'conflict';

    protected function casts(): array
    {
        return [
            'run_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function milkCollections(): HasMany
    {
        return $this->hasMany(MilkCollection::class);
    }
}
