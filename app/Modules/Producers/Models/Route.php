<?php

namespace App\Modules\Producers\Models;

use App\Models\RouteOperatorAssignment;
use App\Models\RouteRun;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Ruteros\Models\Rutero;
use Database\Factories\Modules\Producers\RouteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): RouteFactory
    {
        return RouteFactory::new();
    }

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
        return $this->belongsTo(Company::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
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

    public function routeRuns()
    {
        return $this->hasMany(RouteRun::class);
    }

    public function operatorAssignments()
    {
        return $this->hasMany(RouteOperatorAssignment::class);
    }

    public function rutero()
    {
        return $this->hasOne(Rutero::class);
    }
}
