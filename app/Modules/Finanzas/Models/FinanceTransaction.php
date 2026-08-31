<?php

namespace App\Modules\Finanzas\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'finance_transactions';

    public const TYPE_GASTO = 'gasto';

    public const TYPE_PAGO = 'pago';

    public const TYPE_INGRESO = 'ingreso';

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
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id');
    }
}
