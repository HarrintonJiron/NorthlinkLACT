<?php

namespace App\Modules\Producers\Models;

use App\Models\User;
use App\Modules\Finanzas\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProducerPaymentBatch extends Model
{
    public const STATUS_CLOSED = 'closed';

    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'code', 'week_start', 'week_end', 'status', 'producer_count', 'total_liters',
        'gross_amount', 'advance_amount', 'net_amount', 'closed_by', 'closed_at',
        'paid_by', 'paid_at', 'finance_transaction_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date', 'week_end' => 'date', 'closed_at' => 'datetime', 'paid_at' => 'datetime',
            'total_liters' => 'decimal:2', 'gross_amount' => 'decimal:2',
            'advance_amount' => 'decimal:2', 'net_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProducerPaymentItem::class, 'batch_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function financeTransaction(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class);
    }
}
