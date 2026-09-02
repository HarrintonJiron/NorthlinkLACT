<?php

namespace App\Models;

use App\Modules\Producers\Models\MilkCollection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'device_uuid', 'name', 'platform', 'active', 'last_seen_at'])]
class Device extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routeRuns(): HasMany
    {
        return $this->hasMany(RouteRun::class);
    }

    public function milkCollections(): HasMany
    {
        return $this->hasMany(MilkCollection::class);
    }
}
