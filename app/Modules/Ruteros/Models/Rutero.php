<?php

namespace App\Modules\Ruteros\Models;

use App\Modules\Producers\Models\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rutero extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_id',
        'owner_name',
        'owner_identity_number',
        'owner_phone',
        'vehicle_description',
        'vehicle_plate',
        'driver_name',
        'driver_identity_number',
        'driver_phone',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
