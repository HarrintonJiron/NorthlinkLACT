<?php

namespace App\Modules\Personnel\Services;

use App\Modules\Personnel\Models\AguinaldoItem;
use App\Modules\Personnel\Models\AguinaldoPeriod;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeLoan;
use App\Modules\Personnel\Models\EmployeeSettlement;
use App\Modules\Personnel\Models\EmployeeVacation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeSettlementService
{
    public function __construct(private readonly AguinaldoService $aguinaldoService) {}

    /**
     * Computes the full liquidación breakdown for an employee without persisting
     * anything, so it can be reused for both the initial creation and any later
     * correction (draft/approved) of the settlement.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function calculate(Employee $employee, array $data): array
    {
        $baseSalary = (float) $employee->base_salary;
        $hiredAt = $employee->hired_at;
        $terminationDate = Carbon::parse($data['termination_date'])->startOfDay();
        $tenureDays = max(1, $hiredAt->diffInDays($terminationDate) + 1);

        $pendingStart = Carbon::parse($data['pending_salary_start'])->startOfDay();
        $pendingDays = max(0, $pendingStart->diffInDays($terminationDate) + 1);
        $pendingSalaryAmount = round($baseSalary / 30 * $pendingDays, 2);

        // Vacaciones proporcionales no gozadas: 30 días por cada 360 días trabajados,
        // menos los días de vacaciones ya tomados en toda la relación laboral.
        $accruedVacationDays = round($tenureDays / 360 * 30, 2);
        $takenVacationDays = (float) $employee->vacations()
            ->where('status', EmployeeVacation::STATUS_APPROVED)
            ->sum('days');
        $vacationDaysPending = max(0.0, round($accruedVacationDays - $takenVacationDays, 2));
        $vacationAmount = round($baseSalary / 30 * $vacationDaysPending, 2);

        // Aguinaldo proporcional del periodo Dic-Nov vigente a la fecha de salida,
        // descontando lo que ya se le haya pagado de aguinaldo en ese mismo periodo.
        $aguinaldoYear = $terminationDate->month === 12 ? $terminationDate->year + 1 : $terminationDate->year;
        ['period_start' => $aguinaldoPeriodStart] = $this->aguinaldoService->periodDatesForYear($aguinaldoYear);
        $aguinaldoEmploymentStart = $hiredAt->greaterThan($aguinaldoPeriodStart) ? $hiredAt : $aguinaldoPeriodStart;
        $aguinaldoDays = $aguinaldoEmploymentStart->greaterThan($terminationDate)
            ? 0
            : min(360, $aguinaldoEmploymentStart->diffInDays($terminationDate) + 1);
        $aguinaldoGross = round($baseSalary * $aguinaldoDays / 360, 2);
        $alreadyPaidAguinaldo = (float) AguinaldoItem::query()
            ->where('employee_id', $employee->id)
            ->whereHas('period', fn ($q) => $q->where('year', $aguinaldoYear)->where('status', AguinaldoPeriod::STATUS_PAID))
            ->sum('amount');
        $aguinaldoAmount = max(0.0, round($aguinaldoGross - $alreadyPaidAguinaldo, 2));

        $severanceMethod = $data['severance_method'];
        $severanceAmount = $severanceMethod === EmployeeSettlement::SEVERANCE_METHOD_MANUAL
            ? round((float) ($data['severance_amount'] ?? 0), 2)
            : $this->legalSeveranceAmount($baseSalary, $tenureDays);

        $loanDeduction = (float) $employee->loans()->where('status', EmployeeLoan::STATUS_ACTIVE)->sum('remaining_balance');
        $otherDeduction = (float) $employee->deductions()->where('status', EmployeeDeduction::STATUS_PENDING)->sum('amount');

        $grossAmount = round($pendingSalaryAmount + $vacationAmount + $aguinaldoAmount + $severanceAmount, 2);
        $netAmount = round($grossAmount - $loanDeduction - $otherDeduction, 2);

        return [
            'hired_at' => $hiredAt->toDateString(),
            'termination_date' => $terminationDate->toDateString(),
            'tenure_days' => $tenureDays,
            'pending_salary_start' => $pendingStart->toDateString(),
            'pending_salary_end' => $terminationDate->toDateString(),
            'pending_salary_days' => $pendingDays,
            'pending_salary_amount' => $pendingSalaryAmount,
            'vacation_days_pending' => $vacationDaysPending,
            'vacation_amount' => $vacationAmount,
            'aguinaldo_days' => $aguinaldoDays,
            'aguinaldo_amount' => $aguinaldoAmount,
            'severance_method' => $severanceMethod,
            'severance_amount' => $severanceAmount,
            'loan_deduction' => round($loanDeduction, 2),
            'other_deduction' => round($otherDeduction, 2),
            'gross_amount' => $grossAmount,
            'net_amount' => $netAmount,
        ];
    }

    /**
     * Indemnización por antigüedad, Art. 45 del Código del Trabajo de Nicaragua:
     * 1 mes de salario por cada uno de los primeros 3 años trabajados, luego 20
     * días de salario por cada año adicional, con un tope de 5 meses de salario.
     * Fracciones de año se reconocen proporcionalmente.
     */
    public function legalSeveranceAmount(float $baseSalary, int $tenureDays): float
    {
        $tenureYears = $tenureDays / 365;

        $amount = $tenureYears <= 3
            ? $baseSalary * $tenureYears
            : ($baseSalary * 3) + ($baseSalary * (20 / 30) * ($tenureYears - 3));

        return round(min($amount, $baseSalary * 5), 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Employee $employee, array $data): EmployeeSettlement
    {
        return DB::transaction(function () use ($employee, $data) {
            abort_if(
                EmployeeSettlement::query()->where('employee_id', $employee->id)->exists(),
                422,
                'Este colaborador ya tiene una liquidación registrada.'
            );

            $breakdown = $this->calculate($employee, $data);

            return EmployeeSettlement::query()->create([
                'code' => $this->allocateNextCode(),
                'employee_id' => $employee->id,
                'termination_type' => $data['termination_type'],
                'status' => EmployeeSettlement::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
                ...$breakdown,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EmployeeSettlement $settlement, array $data): EmployeeSettlement
    {
        $breakdown = $this->calculate($settlement->employee, $data);
        $wasApproved = $settlement->status === EmployeeSettlement::STATUS_APPROVED;

        $settlement->update([
            'termination_type' => $data['termination_type'],
            'notes' => $data['notes'] ?? null,
            'status' => $wasApproved ? EmployeeSettlement::STATUS_DRAFT : $settlement->status,
            'approved_at' => $wasApproved ? null : $settlement->approved_at,
            ...$breakdown,
        ]);

        return $settlement;
    }

    /**
     * Actualización atómica UPDATE...WHERE condicionada al estado actual, para
     * que dos aprobaciones simultáneas nunca puedan pasar ambas.
     */
    public function approve(EmployeeSettlement $settlement): bool
    {
        $approvedAt = now();
        $updated = EmployeeSettlement::query()
            ->where('id', $settlement->id)
            ->where('status', EmployeeSettlement::STATUS_DRAFT)
            ->update(['status' => EmployeeSettlement::STATUS_APPROVED, 'approved_at' => $approvedAt]);

        if ($updated > 0) {
            $settlement->forceFill(['status' => EmployeeSettlement::STATUS_APPROVED, 'approved_at' => $approvedAt]);
        }

        return $updated > 0;
    }

    /**
     * La transición de estado se hace primero, de forma atómica y condicionada
     * a que la liquidación siga "aprobada" — si dos peticiones de pago llegan a
     * la vez, solo una gana esa condición, y los efectos (desactivar al
     * colaborador, saldar préstamos y deducciones) solo se ejecutan una vez.
     */
    public function markPaid(EmployeeSettlement $settlement, string $paymentMethod): bool
    {
        return DB::transaction(function () use ($settlement, $paymentMethod) {
            $paidAt = now();
            $updated = EmployeeSettlement::query()
                ->where('id', $settlement->id)
                ->where('status', EmployeeSettlement::STATUS_APPROVED)
                ->update(['status' => EmployeeSettlement::STATUS_PAID, 'paid_at' => $paidAt, 'payment_method' => $paymentMethod]);

            if ($updated === 0) {
                return false;
            }

            $settlement->employee->loans()
                ->where('status', EmployeeLoan::STATUS_ACTIVE)
                ->update(['status' => EmployeeLoan::STATUS_PAID, 'remaining_balance' => 0]);

            $settlement->employee->deductions()
                ->where('status', EmployeeDeduction::STATUS_PENDING)
                ->update(['status' => EmployeeDeduction::STATUS_APPLIED]);

            $settlement->employee->update(['active' => false]);

            $settlement->forceFill(['status' => EmployeeSettlement::STATUS_PAID, 'paid_at' => $paidAt, 'payment_method' => $paymentMethod]);

            return true;
        });
    }

    protected function allocateNextCode(): string
    {
        return 'LIQ-'.str_pad((string) ($this->lastSettlementNumber() + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function lastSettlementNumber(): int
    {
        $max = EmployeeSettlement::query()
            ->where('code', 'like', 'LIQ-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return (int) ($max ?? 0);
    }
}
