<?php

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDispatch extends Model
{
    protected $fillable = ['code', 'dispatch_date', 'destination', 'recipient', 'status', 'notes', 'created_by', 'dispatched_at', 'received_by', 'received_at'];

    protected function casts(): array
    {
        return ['dispatch_date' => 'date', 'dispatched_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(ProductDispatchItem::class, 'dispatch_id');
    }
}
