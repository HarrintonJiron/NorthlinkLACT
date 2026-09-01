<?php

namespace App\Modules\Personnel\Models;

use App\Models\User;
use App\Modules\Admin\Models\Plant;
use Database\Factories\Modules\Personnel\EmployeeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    protected $appends = [
        'full_name',
    ];

    protected $fillable = [
        'code',
        'employee_role_id',
        'first_name',
        'last_name',
        'area',
        'plant_id',
        'identity_number',
        'email',
        'phone',
        'address',
        'hired_at',
        'status',
        'contract_type',
        'contract_start_date',
        'contract_end_date',
        'salary',
        'inss_insured',
        'inss_number',
        'payment_method',
        'bank_account',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'salary' => 'decimal:2',
            'inss_insured' => 'boolean',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(EmployeeRole::class, 'employee_role_id');
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class)->latest('attendance_date');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class)->latest('deduction_date');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class)->latest('id');
    }

    public function isActive(): bool
    {
        return $this->status === 'activo';
    }

    protected static function newFactory(): Factory
    {
        return EmployeeFactory::new();
    }
}
