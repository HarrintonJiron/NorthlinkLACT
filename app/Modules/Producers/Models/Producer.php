<?php

namespace App\Modules\Producers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'full_name',
        'identity_number',
        'phone',
        'address',
        'community',
        'municipality',
        'department',
        'latitude',
        'longitude',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'active' => 'boolean',
        ];
    }

    public function assignments()
    {
        return $this->hasMany(ProducerRouteAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(ProducerRouteAssignment::class)->whereNull('ended_at');
    }

    public function collections()
    {
        return $this->hasMany(MilkCollection::class);
    }
}
