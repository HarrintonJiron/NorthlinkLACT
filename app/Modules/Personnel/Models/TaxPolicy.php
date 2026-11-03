<?php

namespace App\Modules\Personnel\Models;

use Illuminate\Database\Eloquent\Model;

class TaxPolicy extends Model
{
    protected $fillable = [
        'name',
        'effective_from',
        'inss_employee_rate',
        'inss_employer_rate',
        'inatec_rate',
        'ir_brackets',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'inss_employee_rate' => 'decimal:4',
            'inss_employer_rate' => 'decimal:4',
            'inatec_rate' => 'decimal:4',
            'ir_brackets' => 'array',
        ];
    }

    /**
     * The policy in effect for a given date: the most recent one whose
     * effective_from is on or before that date.
     */
    public static function effectiveOn(\DateTimeInterface|string $date): ?self
    {
        return static::query()
            ->where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * IR tax for one full year of taxable income, walking the progressive
     * bracket table stored on this policy. Brackets are ordered ascending by
     * threshold; the last bracket has threshold = null (unbounded top rate).
     */
    public function annualIrTax(float $annual): float
    {
        $brackets = $this->ir_brackets ?? [];
        $base = 0.0;
        $lowerBound = 0.0;

        foreach ($brackets as $bracket) {
            $threshold = $bracket['threshold'] ?? null;
            $rate = (float) ($bracket['rate'] ?? 0);

            if ($threshold === null || $annual <= $threshold) {
                return $base + max(0, $annual - $lowerBound) * $rate;
            }

            $base += ($threshold - $lowerBound) * $rate;
            $lowerBound = (float) $threshold;
        }

        return $base;
    }
}
