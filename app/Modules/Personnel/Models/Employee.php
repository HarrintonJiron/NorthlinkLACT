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

    public const FREQ_WEEKLY = 'weekly';

    public const FREQ_BIWEEKLY = 'biweekly';

    public const FREQ_MONTHLY = 'monthly';

    protected $appends = [
        'active',
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
        'base_salary',
        'pay_frequency',
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
            'base_salary' => 'decimal:2',
            'pay_frequency' => 'string',
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

    /**
     * @return Attribute<bool, never>
     */
    protected function active(): Attribute
    {
        return Attribute::get(fn (): bool => $this->isActive());
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

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(EmployeeVacation::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(EmployeeLeave::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(EmployeeAbsence::class);
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(EmployeeSettlement::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'activo';
    }

    public function tenureInDays(): int
    {
        return $this->hired_at ? (int) $this->hired_at->diffInDays(now()) : 0;
    }

    public function vacationBalance(): float
    {
        $accruedDays = round($this->tenureInDays() / 360 * 30, 2);
        $takenDays = (float) $this->vacations()->where('status', 'approved')->sum('days');

        return max(0.0, round($accruedDays - $takenDays, 2));
    }

    protected static function newFactory(): Factory
    {
        return EmployeeFactory::new();
    }
}
