<?php

namespace App\Modules\Production\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionWeightEntry extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CORRECTED = 'corrected';

    protected $fillable = [
        'production_batch_id', 'sequence', 'weight_lb', 'scale_reference',
        'weighed_at', 'weighed_by', 'original_entry_id', 'status', 'correction_reason',
    ];

    protected function casts(): array
    {
        return ['weight_lb' => 'decimal:3', 'weighed_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function weighedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'weighed_by');
    }

    public function originalEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_entry_id');
    }
}
