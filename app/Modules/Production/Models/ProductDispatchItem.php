<?php

namespace App\Modules\Production\Models;

use App\Modules\Inventory\Models\InventoryProduct;
use Illuminate\Database\Eloquent\Model;

class ProductDispatchItem extends Model
{
    protected $fillable = ['dispatch_id', 'finished_product_lot_id', 'inventory_product_id', 'quantity', 'unit_cost'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:4'];
    }

    public function dispatch()
    {
        return $this->belongsTo(ProductDispatch::class);
    }

    public function lot()
    {
        return $this->belongsTo(FinishedProductLot::class, 'finished_product_lot_id');
    }

    public function product()
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }
}
