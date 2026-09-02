<?php

namespace App\Modules\Personnel\Models;

use App\Models\User;
use Database\Factories\Modules\Personnel\EmployeeFactory;
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

    protected $fillable = [
        'employee_role_id',
        'full_name',
        'identity_number',
        'email',
        'phone',
        'hired_at',
        'active',
        'base_salary',
        'pay_frequency',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'hired_at' => 'date',
            'base_salary' => 'decimal:2',
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

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(EmployeeSettlement::class);
    }

    public function tenureInDays(): int
    {
        return $this->hired_at ? (int) $this->hired_at->diffInDays(now()) : 0;
    }

    /**
     * Días de vacaciones disponibles hoy: 30 días acumulados por cada 360 días
     * de antigüedad, menos los ya tomados en solicitudes aprobadas. Misma
     * convención de año comercial de 360 días que ya usan AguinaldoService y
     * EmployeeSettlementService.
     */
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
