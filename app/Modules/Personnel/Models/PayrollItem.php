<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'base_salary',
        'bonus_amount',
        'deduction_amount',
        'gross_salary',
        'inss_employee',
        'ir_amount',
        'other_deductions',
        'loan_deduction',
        'days_worked',
        'vacation_days',
        'leave_days',
        'absence_days',
        'net_pay',
        'inss_employer',
        'inatec_employer',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'inss_employee' => 'decimal:2',
            'ir_amount' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'inss_employer' => 'decimal:2',
            'inatec_employer' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function bonuses(): HasMany
    {
        return $this->hasMany(EmployeeBonus::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function loanDeductions(): HasMany
    {
        return $this->hasMany(PayrollItemLoanDeduction::class);
    }
}
