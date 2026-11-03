<?php

namespace App\Modules\Inventory\Models;

use Database\Factories\Modules\Inventory\ProductPresentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPresentation extends Model
{
    use HasFactory;

    protected $table = 'product_presentations';

    protected $fillable = [
        'product_id',
        'name',
        'description',
        'unit_id',
        'conversion_factor',
        'price',
        'default',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'price' => 'decimal:2',
            'default' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'unit_id');
    }

    protected static function newFactory(): ProductPresentationFactory
    {
        return ProductPresentationFactory::new();
    }
}
