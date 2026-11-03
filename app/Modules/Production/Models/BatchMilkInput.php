<?php

namespace App\Modules\Production\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchMilkInput extends Model
{
    protected $fillable = ['production_batch_id', 'source_date', 'liters', 'recorded_by'];

    protected function casts(): array
    {
        return ['source_date' => 'date', 'liters' => 'decimal:3'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
