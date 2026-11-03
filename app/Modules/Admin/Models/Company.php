<?php

namespace App\Modules\Admin\Models;

use App\Models\User;
use Database\Factories\Modules\Admin\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'address',
        'phone',
        'email',
        'currency',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('plant_id')
            ->withTimestamps();
    }
}
