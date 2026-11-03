<?php

namespace App\Modules\Producers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducerPaymentItem extends Model
{
    protected $fillable = [
        'batch_id', 'producer_id', 'route_id', 'producer_code', 'producer_name', 'route_code',
        'liters', 'price_per_liter', 'gross_amount', 'advance_amount', 'net_amount',
        'daily_liters', 'average_density', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'daily_liters' => 'array', 'liters' => 'decimal:2', 'price_per_liter' => 'decimal:4',
            'gross_amount' => 'decimal:2', 'advance_amount' => 'decimal:2',
            'net_amount' => 'decimal:2', 'average_density' => 'decimal:3',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProducerPaymentBatch::class, 'batch_id');
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
