<?php

namespace App\Modules\Producers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'plant_id',
        'code',
        'name',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Company::class);
    }

    public function plant()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Plant::class);
    }

    public function assignments()
    {
        return $this->hasMany(ProducerRouteAssignment::class);
    }

    public function collections()
    {
        return $this->hasMany(MilkCollection::class);
    }
}
