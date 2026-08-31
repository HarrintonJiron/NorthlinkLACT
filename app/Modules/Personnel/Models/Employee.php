<?php

namespace App\Modules\Personnel\Models;

use App\Models\User;
use Database\Factories\Modules\Personnel\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_role_id',
        'full_name',
        'identity_number',
        'email',
        'phone',
        'hired_at',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'hired_at' => 'date',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(EmployeeRole::class, 'employee_role_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    protected static function newFactory(): Factory
    {
        return EmployeeFactory::new();
    }
}
