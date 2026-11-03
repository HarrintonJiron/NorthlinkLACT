<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use Database\Factories\Modules\Admin\PlantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plant extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PlantFactory
    {
        return PlantFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'phone',
        'email',
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

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('company_id')
            ->withTimestamps();
    }
}
