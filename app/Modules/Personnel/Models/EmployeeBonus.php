<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBonus extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPLIED = 'applied';

    protected $fillable = [
        'employee_id',
        'concept',
        'amount',
        'bonus_date',
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
            'bonus_date' => 'date',
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
}
