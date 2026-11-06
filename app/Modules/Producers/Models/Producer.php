<?php

namespace App\Modules\Producers\Models;

use Database\Factories\Modules\Producers\ProducerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producer extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): ProducerFactory
    {
        return ProducerFactory::new();
    }

    protected $fillable = [
        'code',
        'external_uuid',
        'full_name',
        'identity_number',
        'phone',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'preferred_price_per_liter',
        'payment_notes',
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
            'bank_account_number' => 'encrypted',
            'preferred_price_per_liter' => 'decimal:4',
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
