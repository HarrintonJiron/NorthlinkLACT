<?php

namespace App\Modules\Production\Models;

use App\Modules\Inventory\Models\InventoryProduct;
use Illuminate\Database\Eloquent\Model;

class FinishedProductLot extends Model
{
    protected $fillable = ['production_batch_id', 'inventory_product_id', 'lot_number', 'quantity', 'available_quantity', 'unit_cost', 'expiration_date', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'available_quantity' => 'decimal:3', 'unit_cost' => 'decimal:4', 'expiration_date' => 'date'];
    }

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function product()
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }
}
