<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryProduct extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_products';

    protected $fillable = [
        'code',
        'name',
        'description',
        'unit_id',
        'stock',
        'min_stock',
        'expiration_date',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'expiration_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }
}
