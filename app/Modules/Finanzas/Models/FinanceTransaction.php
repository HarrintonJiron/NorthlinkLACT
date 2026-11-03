<?php

namespace App\Modules\Finanzas\Models;

use Database\Factories\Modules\Finanzas\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): FinanceTransactionFactory
    {
        return FinanceTransactionFactory::new();
    }

    protected $table = 'finance_transactions';

    public const TYPE_GASTO = 'gasto';

    public const TYPE_PAGO = 'pago';

    public const TYPE_INGRESO = 'ingreso';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    public const STATUS_RECONCILED = 'reconciled';

    public const STATUS_VOIDED = 'voided';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REVIEWED,
        self::STATUS_APPROVED,
        self::STATUS_PAID,
        self::STATUS_RECONCILED,
        self::STATUS_VOIDED,
    ];

    protected $fillable = [
        'code',
        'type',
        'category_id',
        'concept',
        'description',
        'amount',
        'transaction_date',
        'payment_method',
        'reference',
        'payee',
        'notes',
        'active',
        'status',
        'void_reason',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
        'reconciled_by',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'active' => 'boolean',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }
}
