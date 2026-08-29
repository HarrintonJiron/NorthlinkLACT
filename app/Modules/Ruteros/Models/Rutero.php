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
        'full_name',
        'identity_number',
        'phone',
        'vehicle_plate',
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
