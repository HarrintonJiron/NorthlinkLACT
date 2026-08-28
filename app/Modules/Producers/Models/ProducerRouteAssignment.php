<?php

namespace App\Modules\Producers\Models;

use Illuminate\Database\Eloquent\Model;

class ProducerRouteAssignment extends Model
{
    protected $fillable = [
        'producer_id',
        'route_id',
        'assigned_at',
        'ended_at',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }
}
