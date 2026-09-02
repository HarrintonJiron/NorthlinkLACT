<?php

namespace App\Modules\Personnel\Services;

use App\Modules\Personnel\Models\AguinaldoItem;
use App\Modules\Personnel\Models\AguinaldoPeriod;
use App\Modules\Personnel\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AguinaldoService
{
    /**
     * The legal Nicaraguan aguinaldo period is Dec 1 of the prior year through
     * Nov 30 of the given year, and it is exempt from INSS and IR (Ley del
     * Décimo Tercer Mes). A full period worked pays exactly one month's salary.
     */
    public function periodDatesForYear(int $year): array
    {
        return [
            'period_start' => Carbon::create($year - 1, 12, 1)->startOfDay(),
            'period_end' => Carbon::create($year, 11, 30)->startOfDay(),
        ];
    }

    public function generatePeriod(int $year, ?string $notes = null): AguinaldoPeriod
    {
        return DB::transaction(function () use ($year, $notes) {
            // El formulario ya valida esto (año único), pero se repite aquí para que
            // el servicio nunca dependa únicamente de esa validación si se invoca
            // desde otro lugar (comando, cola, etc.), en vez de dejar que la
            // restricción única de la base de datos lo reviente sin control.
            if (AguinaldoPeriod::query()->where('year', $year)->exists()) {
                throw new RuntimeException("Ya existe un aguinaldo generado para el año {$year}.");
            }

            ['period_start' => $periodStart, 'period_end' => $periodEnd] = $this->periodDatesForYear($year);
            $today = now()->startOfDay();
            $effectiveEnd = $periodEnd->lessThan($today) ? $periodEnd : $today;

            $period = AguinaldoPeriod::query()->create([
                'code' => $this->allocateNextCode(),
                'year' => $year,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => AguinaldoPeriod::STATUS_DRAFT,
                'notes' => $notes,
            ]);

            $employees = Employee::query()
                ->where('active', true)
                ->whereNotNull('base_salary')
                ->where('base_salary', '>', 0)
                ->where('hired_at', '<=', $effectiveEnd)
                ->get();

            foreach ($employees as $employee) {
                $employmentStart = $employee->hired_at->greaterThan($periodStart) ? $employee->hired_at : $periodStart;

                if ($employmentStart->greaterThan($effectiveEnd)) {
                    continue;
                }

                $daysEmployed = min(360, $employmentStart->diffInDays($effectiveEnd) + 1);
                $baseSalary = (float) $employee->base_salary;
                $amount = round($baseSalary * $daysEmployed / 360, 2);

                AguinaldoItem::query()->create([
                    'aguinaldo_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'base_salary' => $baseSalary,
                    'days_employed' => $daysEmployed,
                    'amount' => $amount,
                ]);
            }

            return $period->load('items.employee.role');
        });
    }

    /**
     * Actualización atómica UPDATE...WHERE condicionada al estado actual, para
     * que dos aprobaciones simultáneas nunca puedan pasar ambas.
     */
    public function approve(AguinaldoPeriod $period): bool
    {
        $approvedAt = now();
        $updated = AguinaldoPeriod::query()
            ->where('id', $period->id)
            ->where('status', AguinaldoPeriod::STATUS_DRAFT)
            ->update(['status' => AguinaldoPeriod::STATUS_APPROVED, 'approved_at' => $approvedAt]);

        if ($updated > 0) {
            $period->forceFill(['status' => AguinaldoPeriod::STATUS_APPROVED, 'approved_at' => $approvedAt]);
        }

        return $updated > 0;
    }

    public function markPaid(AguinaldoPeriod $period): bool
    {
        $paidAt = now();
        $updated = AguinaldoPeriod::query()
            ->where('id', $period->id)
            ->where('status', AguinaldoPeriod::STATUS_APPROVED)
            ->update(['status' => AguinaldoPeriod::STATUS_PAID, 'paid_at' => $paidAt]);

        if ($updated > 0) {
            $period->forceFill(['status' => AguinaldoPeriod::STATUS_PAID, 'paid_at' => $paidAt]);
        }

        return $updated > 0;
    }

    protected function allocateNextCode(): string
    {
        return 'AGU-'.str_pad((string) ($this->lastPeriodNumber() + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function lastPeriodNumber(): int
    {
        $max = AguinaldoPeriod::query()
            ->where('code', 'like', 'AGU-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return (int) ($max ?? 0);
    }
}
