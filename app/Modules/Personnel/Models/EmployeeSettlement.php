<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSettlement extends Model
{
    public const TYPE_UNJUSTIFIED_DISMISSAL = 'unjustified_dismissal';

    public const TYPE_RESIGNATION = 'resignation';

    public const TYPE_JUSTIFIED_DISMISSAL = 'justified_dismissal';

    public const TYPE_MUTUAL_AGREEMENT = 'mutual_agreement';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const SEVERANCE_METHOD_LEGAL = 'legal';

    public const SEVERANCE_METHOD_MANUAL = 'manual';

    protected $fillable = [
        'code',
        'employee_id',
        'termination_type',
        'hired_at',
        'termination_date',
        'tenure_days',
        'pending_salary_start',
        'pending_salary_end',
        'pending_salary_days',
        'pending_salary_amount',
        'vacation_days_pending',
        'vacation_amount',
        'aguinaldo_days',
        'aguinaldo_amount',
        'severance_method',
        'severance_amount',
        'loan_deduction',
        'other_deduction',
        'gross_amount',
        'net_amount',
        'status',
        'payment_method',
        'approved_at',
        'paid_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
            'termination_date' => 'date',
            'pending_salary_start' => 'date',
            'pending_salary_end' => 'date',
            'pending_salary_amount' => 'decimal:2',
            'vacation_days_pending' => 'decimal:2',
            'vacation_amount' => 'decimal:2',
            'aguinaldo_days' => 'decimal:2',
            'aguinaldo_amount' => 'decimal:2',
            'severance_amount' => 'decimal:2',
            'loan_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
