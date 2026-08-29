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

    public function activeAssignments()
    {
        return $this->hasMany(ProducerRouteAssignment::class)->whereNull('ended_at');
    }

    public function collections()
    {
        return $this->hasMany(MilkCollection::class);
    }

    public function rutero()
    {
        return $this->hasOne(\App\Modules\Ruteros\Models\Rutero::class);
    }
}
