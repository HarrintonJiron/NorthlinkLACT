<?php

namespace App\Modules\Producers\Models;

use Illuminate\Database\Eloquent\Model;

class ProducerWeekAdjustment extends Model
{
    protected $fillable = [
        'producer_id',
        'week_end',
        'density_price',
        'advance_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_end' => 'date',
            'density_price' => 'decimal:4',
            'advance_amount' => 'decimal:2',
        ];
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }
}
