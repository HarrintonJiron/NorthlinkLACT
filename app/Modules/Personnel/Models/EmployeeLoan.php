<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeLoan extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'employee_id',
        'amount',
        'reason',
        'installment_amount',
        'remaining_balance',
        'status',
        'granted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'granted_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollItemLoanDeduction::class);
    }
}
