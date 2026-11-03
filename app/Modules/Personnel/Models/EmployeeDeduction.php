<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDeduction extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'employee_id',
        'type',
        'concept',
        'amount',
        'total_amount',
        'installment_amount',
        'installments_total',
        'installments_paid',
        'deduction_date',
        'reason',
        'status',
        'payroll_item_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'deduction_date' => 'date',
            'installments_total' => 'integer',
            'installments_paid' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function installmentsPending(): ?int
    {
        if ($this->installments_total === null) {
            return null;
        }

        return max(0, $this->installments_total - $this->installments_paid);
    }

    public function supportsInstallments(): bool
    {
        return in_array($this->type, ['adelanto_salario', 'prestamo', 'anticipo'], true);
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeDeduction $deduction): void {
            $deduction->type ??= 'otra';
            $deduction->reason ??= $deduction->concept;
        });
    }
}
