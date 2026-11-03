<?php

namespace App\Modules\Inventory\Models;

use Database\Factories\Modules\Inventory\InventoryProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): InventoryProductFactory
    {
        return InventoryProductFactory::new();
    }

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

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class, 'product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'product_id');
    }

    public function defaultPresentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'product_id')
            ->where('default', true);
    }
}
