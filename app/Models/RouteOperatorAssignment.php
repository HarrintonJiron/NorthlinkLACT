<?php

namespace App\Models;

use App\Modules\Producers\Models\Route;
use Illuminate\Database\Eloquent\Model;

class RouteOperatorAssignment extends Model
{
    protected $fillable = [
        'route_id',
        'user_id',
        'assigned_date',
        'assigned_by',
        'vehicle_plate',
        'is_shared_test',
    ];

    protected function casts(): array
    {
        return [
            'assigned_date' => 'date',
            'is_shared_test' => 'boolean',
        ];
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
