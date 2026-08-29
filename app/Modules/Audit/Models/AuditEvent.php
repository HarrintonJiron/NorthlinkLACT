<?php

namespace App\Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'company_id',
        'plant_id',
        'entity_type',
        'entity_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Company::class);
    }

    public function plant()
    {
        return $this->belongsTo(\App\Modules\Admin\Models\Plant::class);
    }
}
