<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

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
        return $this->belongsToMany(\App\Models\User::class, 'company_user')
            ->withPivot('plant_id')
            ->withTimestamps();
    }
}
