<?php

namespace App\Modules\Inventory\Models;

use Database\Factories\Modules\Inventory\InventoryUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryUnit extends Model
{
    use HasFactory;

    protected static function newFactory(): InventoryUnitFactory
    {
        return InventoryUnitFactory::new();
    }

    protected $table = 'inventory_units';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(InventoryProduct::class, 'unit_id');
    }
}
