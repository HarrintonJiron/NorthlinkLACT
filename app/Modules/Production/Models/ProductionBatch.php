<?php

namespace App\Modules\Production\Models;

use App\Models\User;
use App\Modules\Admin\Models\Plant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatch extends Model
{
    public const STATUS_PLANNED = 'planned';

    public const STATUS_IN_PROCESS = 'in_process';

    public const STATUS_WEIGHING = 'weighing';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'plant_id', 'batch_number', 'status', 'started_at', 'completed_at',
        'cream_weight_lb', 'trimmings_weight_lb', 'reprocess_weight_lb',
        'waste_weight_lb', 'notes', 'created_by', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cream_weight_lb' => 'decimal:3',
            'trimmings_weight_lb' => 'decimal:3',
            'reprocess_weight_lb' => 'decimal:3',
            'waste_weight_lb' => 'decimal:3',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function milkInputs(): HasMany
    {
        return $this->hasMany(BatchMilkInput::class);
    }

    public function weightEntries(): HasMany
    {
        return $this->hasMany(ProductionWeightEntry::class);
    }

    public function activeWeightEntries(): HasMany
    {
        return $this->weightEntries()->where('status', ProductionWeightEntry::STATUS_ACTIVE);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(ProductionCostEntry::class);
    }

    public function finishedLots(): HasMany
    {
        return $this->hasMany(FinishedProductLot::class);
    }
}
