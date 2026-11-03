<?php

namespace App\Modules\Finanzas\Models;

use Database\Factories\Modules\Finanzas\FinanceCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceCategory extends Model
{
    use HasFactory;

    protected static function newFactory(): FinanceCategoryFactory
    {
        return FinanceCategoryFactory::new();
    }

    protected $table = 'finance_categories';

    protected $fillable = [
        'code',
        'name',
        'type',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }
}
