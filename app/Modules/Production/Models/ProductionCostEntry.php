<?php

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionCostEntry extends Model
{
    protected $fillable = ['production_batch_id', 'category', 'description', 'amount', 'recorded_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }
}
