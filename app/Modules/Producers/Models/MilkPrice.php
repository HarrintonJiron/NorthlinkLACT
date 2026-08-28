<?php

namespace App\Modules\Producers\Models;

use Illuminate\Database\Eloquent\Model;

class MilkPrice extends Model
{
    protected $fillable = [
        'company_id',
        'plant_id',
        'price_per_liter',
        'effective_from',
        'effective_until',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_liter' => 'decimal:4',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'active' => 'boolean',
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

    public function scopeEffectiveOn($query, $date)
    {
        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            })
            ->where('active', true);
    }
}
