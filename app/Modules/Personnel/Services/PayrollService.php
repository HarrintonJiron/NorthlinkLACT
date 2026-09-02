<?php

namespace App\Modules\Personnel\Services;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeBonus;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeLeave;
use App\Modules\Personnel\Models\EmployeeLoan;
use App\Modules\Personnel\Models\PayrollItem;
use App\Modules\Personnel\Models\PayrollItemLoanDeduction;
use App\Modules\Personnel\Models\PayrollPeriod;
use App\Modules\Personnel\Models\TaxPolicy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollService
{
    /**
     * Periods per year for each pay frequency, used to annualize/de-annualize IR.
     *
     * @return array<string, int>
     */
    public function periodsPerYear(): array
    {
        return [
            Employee::FREQ_WEEKLY => 52,
            Employee::FREQ_BIWEEKLY => 24,
            Employee::FREQ_MONTHLY => 12,
        ];
    }

    public function calculateInssEmployee(float $gross, TaxPolicy $policy): float
    {
        return round($gross * (float) $policy->inss_employee_rate, 2);
    }

    public function calculateInssEmployer(float $gross, TaxPolicy $policy): float
    {
        return round($gross * (float) $policy->inss_employer_rate, 2);
    }

    public function calculateInatecEmployer(float $gross, TaxPolicy $policy): float
    {
        return round($gross * (float) $policy->inatec_rate, 2);
    }

    /**
     * IR (Impuesto sobre la Renta) withholding for one pay period, based on the
     * progressive annual bracket table stored on the given tax policy.
     */
    public function calculateIr(float $gross, float $inssEmployee, string $payFrequency, TaxPolicy $policy): float
    {
        $periods = $this->periodsPerYear()[$payFrequency] ?? 12;
        $taxableAnnual = max(0, ($gross - $inssEmployee) * $periods);
        $annualTax = $policy->annualIrTax($taxableAnnual);

        return round($annualTax / $periods, 2);
    }

    public function generatePeriod(array $data): PayrollPeriod
    {
        return DB::transaction(function () use ($data) {
            $periodEnd = $data['period_end'];
            $policy = TaxPolicy::effectiveOn($periodEnd);

            if (! $policy) {
                throw new RuntimeException('No hay una política de impuestos vigente para esta fecha. Crea una en Nómina → Impuestos.');
            }

            $period = PayrollPeriod::query()->create([
                'code' => $this->allocateNextCode(),
                'pay_frequency' => $data['pay_frequency'],
                'tax_policy_id' => $policy->id,
                'period_start' => $data['period_start'],
                'period_end' => $periodEnd,
                'status' => PayrollPeriod::STATUS_DRAFT,
                'notes' => $data['notes'] ?? null,
            ]);

            $periodStart = $period->period_start;
            $periodEndDate = $period->period_end;
            $periodDays = $periodStart->diffInDays($periodEndDate) + 1;

            // Una sola planilla incluye a todos los colaboradores activos con sueldo,
            // sin importar su frecuencia individual; cada uno se prorratea por sus
            // días reales dentro del periodo. La frecuencia elegida en el formulario
            // solo se usa para anualizar el IR de esta corrida.
            //
            // Los bonos, deducciones, vacaciones, ausencias, permisos y préstamos de
            // todos los colaboradores se precargan aquí en una sola tanda de consultas
            // (con sus filtros ya aplicados), en vez de consultarlos uno por uno dentro
            // del bucle — evitar ~7 consultas por colaborador es lo que hace viable
            // generar una planilla con cientos o miles de colaboradores.
            $employees = Employee::query()
                ->where('active', true)
                ->whereNotNull('base_salary')
                ->where('base_salary', '>', 0)
                ->with([
                    'bonuses' => fn ($q) => $q->where('status', EmployeeBonus::STATUS_PENDING),
                    'deductions' => fn ($q) => $q->where('status', EmployeeDeduction::STATUS_PENDING),
                    'vacations' => fn ($q) => $q->where('status', 'approved'),
                    'absences' => fn ($q) => $q->whereBetween('date', [$periodStart, $periodEndDate]),
                    'leaves' => fn ($q) => $q->where('status', EmployeeLeave::STATUS_APPROVED),
                    'loans' => fn ($q) => $q->where('status', EmployeeLoan::STATUS_ACTIVE),
                ])
                ->get();

            foreach ($employees as $employee) {
                $baseSalary = (float) $employee->base_salary;

                $pendingBonuses = $employee->bonuses;
                $bonusAmount = (float) $pendingBonuses->sum('amount');

                $pendingDeductions = $employee->deductions;
                $deductionAmount = (float) $pendingDeductions->sum('amount');

                $periodVacations = $employee->vacations
                    ->map(fn ($vacation) => [
                        'days' => $this->overlappingDays($vacation->start_date, $vacation->end_date, $periodStart, $periodEndDate),
                        'paid' => $vacation->paid,
                        'start' => $vacation->start_date,
                        'end' => $vacation->end_date,
                    ])
                    ->filter(fn ($vacation) => $vacation['days'] > 0);
                $vacationDays = $periodVacations->sum('days');

                $periodAbsences = $employee->absences;
                $absenceDays = $periodAbsences->count();

                $periodLeaves = $employee->leaves
                    ->map(fn ($leave) => [
                        'days' => $this->overlappingDays($leave->start_date, $leave->end_date, $periodStart, $periodEndDate),
                        'paid' => $leave->paid,
                        'start' => $leave->start_date,
                        'end' => $leave->end_date,
                    ])
                    ->filter(fn ($leave) => $leave['days'] > 0);
                $leaveDays = $periodLeaves->sum('days');

                // Conjunto de fechas concretas sin goce (ausencias injustificadas +
                // vacaciones/permisos sin goce), para no descontar dos veces un mismo día
                // que caiga simultáneamente en más de una de estas categorías (p. ej. una
                // ausencia injustificada dentro del rango de una vacación sin goce).
                $unpaidDates = [];
                foreach ($periodAbsences->where('type', 'unjustified') as $absence) {
                    $unpaidDates[$absence->date->format('Y-m-d')] = true;
                }
                foreach ($periodVacations->where('paid', false) as $vacation) {
                    foreach ($this->overlappingDateSet($vacation['start'], $vacation['end'], $periodStart, $periodEndDate) as $date) {
                        $unpaidDates[$date] = true;
                    }
                }
                foreach ($periodLeaves->where('paid', false) as $leave) {
                    foreach ($this->overlappingDateSet($leave['start'], $leave['end'], $periodStart, $periodEndDate) as $date) {
                        $unpaidDates[$date] = true;
                    }
                }
                $unpaidDaysCount = count($unpaidDates);

                // Vacaciones y permisos con goce de salario, y ausencias justificadas, se
                // pagan; solo las ausencias injustificadas y las vacaciones/permisos sin
                // goce descuentan del sueldo, igual que en la planilla original del
                // cliente (sueldo mensual / 30 x días pagados).
                //
                // Cuando el periodo es un mes calendario completo (día 1 al último día
                // del mes) con frecuencia mensual, el sueldo se prorratea sobre un mes
                // comercial de 30 días fijos, no el largo real del mes: con asistencia
                // completa el bruto siempre debe ser exactamente el sueldo base, sin
                // importar si ese mes tiene 28, 29, 30 o 31 días. Usar el conteo
                // calendario real como base sobre-paga en los meses de 31 días y
                // sub-paga febrero. Un rango mensual parcial (p. ej. alguien que entra
                // a mitad de mes) sigue prorateando sobre sus días reales.
                $isFullCalendarMonth = $data['pay_frequency'] === Employee::FREQ_MONTHLY
                    && $periodStart->day === 1
                    && $periodEndDate->isSameDay($periodStart->copy()->endOfMonth());
                $baselineDays = $isFullCalendarMonth ? 30 : $periodDays;
                $paidDays = max(0, $baselineDays - $unpaidDaysCount);
                $daysWorked = max(0, $periodDays - $vacationDays - $absenceDays - $leaveDays);

                $proratedBase = round(($baseSalary / 30) * $paidDays, 2);
                $gross = round($proratedBase + $bonusAmount, 2);
                $inssEmployee = $this->calculateInssEmployee($gross, $policy);
                // El IR se anualiza con la frecuencia de pago propia del colaborador
                // (no con la de esta planilla en particular), ya que todos los
                // colaboradores entran a cada planilla sin importar su frecuencia, y
                // cada quien debe anualizarse según cuántas veces al año cobra en
                // realidad.
                $ir = $this->calculateIr($gross, $inssEmployee, $employee->pay_frequency, $policy);

                $activeLoans = $employee->loans;
                $loanDeduction = 0.0;

                // Una deducción registrada nunca debe dejar un neto negativo: se aplica
                // hasta donde alcance lo devengado en este periodo, igual que ya se hace
                // con las cuotas de préstamo más abajo.
                $netBeforeLoans = max(0, round($gross - $inssEmployee - $ir - $deductionAmount, 2));

                $item = PayrollItem::query()->create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'base_salary' => $baseSalary,
                    'bonus_amount' => $bonusAmount,
                    'deduction_amount' => $deductionAmount,
                    'gross_salary' => $gross,
                    'inss_employee' => $inssEmployee,
                    'ir_amount' => $ir,
                    'other_deductions' => 0,
                    'loan_deduction' => 0,
                    'days_worked' => $daysWorked,
                    'vacation_days' => $vacationDays,
                    'leave_days' => $leaveDays,
                    'absence_days' => $absenceDays,
                    'net_pay' => $netBeforeLoans,
                    'inss_employer' => $this->calculateInssEmployer($gross, $policy),
                    'inatec_employer' => $this->calculateInatecEmployer($gross, $policy),
                ]);

                if ($pendingBonuses->isNotEmpty()) {
                    EmployeeBonus::query()
                        ->whereIn('id', $pendingBonuses->pluck('id'))
                        ->update(['status' => EmployeeBonus::STATUS_APPLIED, 'payroll_item_id' => $item->id]);
                }

                if ($pendingDeductions->isNotEmpty()) {
                    EmployeeDeduction::query()
                        ->whereIn('id', $pendingDeductions->pluck('id'))
                        ->update(['status' => EmployeeDeduction::STATUS_APPLIED, 'payroll_item_id' => $item->id]);
                }

                foreach ($activeLoans as $loanStub) {
                    // Se vuelve a leer el préstamo con bloqueo de fila justo antes de
                    // descontarlo (no se usa el saldo precargado al inicio de la
                    // transacción), para que dos planillas generándose al mismo tiempo
                    // sobre el mismo préstamo se serialicen correctamente en vez de que
                    // una sobrescriba el descuento de la otra con un saldo desactualizado.
                    $loan = EmployeeLoan::query()->lockForUpdate()->find($loanStub->id);

                    if (! $loan || $loan->status !== EmployeeLoan::STATUS_ACTIVE) {
                        continue;
                    }

                    $remaining = (float) $loan->remaining_balance;

                    if ($remaining <= 0) {
                        continue;
                    }

                    $installment = min((float) $loan->installment_amount, $remaining, max(0, $netBeforeLoans - $loanDeduction));

                    if ($installment <= 0) {
                        continue;
                    }

                    PayrollItemLoanDeduction::query()->create([
                        'payroll_item_id' => $item->id,
                        'employee_loan_id' => $loan->id,
                        'amount' => $installment,
                    ]);

                    $newBalance = round($remaining - $installment, 2);
                    $loan->update([
                        'remaining_balance' => $newBalance,
                        'status' => $newBalance <= 0 ? EmployeeLoan::STATUS_PAID : EmployeeLoan::STATUS_ACTIVE,
                    ]);

                    $loanDeduction += $installment;
                }

                if ($loanDeduction > 0) {
                    $item->update([
                        'loan_deduction' => round($loanDeduction, 2),
                        'net_pay' => round($netBeforeLoans - $loanDeduction, 2),
                    ]);
                }
            }

            return $period->load('items.employee.role');
        });
    }

    /**
     * Corrects a single line of a draft or approved planilla. The employee, INSS
     * employer/INATEC and days worked stay as originally generated; only the gross
     * pay and any manual deduction can be overridden, and INSS/IR/neto are
     * recalculated from those (using the tax policy the planilla was generated
     * with) so the legal formulas are never bypassed.
     */
    public function recalculateItem(PayrollItem $item, float $grossSalary, float $otherDeductions): PayrollItem
    {
        $policy = $item->period->taxPolicy;
        $inssEmployee = $this->calculateInssEmployee($grossSalary, $policy);
        $ir = $this->calculateIr($grossSalary, $inssEmployee, $item->employee->pay_frequency, $policy);
        $loanDeduction = (float) $item->loan_deduction;
        $deductionAmount = (float) $item->deduction_amount;
        $netPay = max(0, round($grossSalary - $inssEmployee - $ir - $loanDeduction - $deductionAmount - $otherDeductions, 2));

        $item->update([
            'gross_salary' => round($grossSalary, 2),
            'inss_employee' => $inssEmployee,
            'ir_amount' => $ir,
            'other_deductions' => round($otherDeductions, 2),
            'net_pay' => $netPay,
            'inss_employer' => $this->calculateInssEmployer($grossSalary, $policy),
            'inatec_employer' => $this->calculateInatecEmployer($grossSalary, $policy),
        ]);

        return $item;
    }

    /**
     * Aprueba la planilla solo si sigue en borrador, con una única sentencia
     * UPDATE...WHERE atómica en vez de leer el estado y escribirlo después por
     * separado — así dos aprobaciones simultáneas nunca pueden pasar ambas: la
     * base de datos garantiza que solo una fila coincide con la condición.
     */
    public function approve(PayrollPeriod $period): bool
    {
        $approvedAt = now();
        $updated = PayrollPeriod::query()
            ->where('id', $period->id)
            ->where('status', PayrollPeriod::STATUS_DRAFT)
            ->update(['status' => PayrollPeriod::STATUS_APPROVED, 'approved_at' => $approvedAt]);

        if ($updated > 0) {
            $period->forceFill(['status' => PayrollPeriod::STATUS_APPROVED, 'approved_at' => $approvedAt]);
        }

        return $updated > 0;
    }

    /**
     * Igual que approve(): actualización atómica condicionada al estado actual.
     */
    public function markPaid(PayrollPeriod $period, string $paymentMethod): bool
    {
        $paidAt = now();
        $updated = PayrollPeriod::query()
            ->where('id', $period->id)
            ->where('status', PayrollPeriod::STATUS_APPROVED)
            ->update(['status' => PayrollPeriod::STATUS_PAID, 'paid_at' => $paidAt, 'payment_method' => $paymentMethod]);

        if ($updated > 0) {
            $period->forceFill(['status' => PayrollPeriod::STATUS_PAID, 'paid_at' => $paidAt, 'payment_method' => $paymentMethod]);
        }

        return $updated > 0;
    }

    private function overlappingDays($startA, $endA, $startB, $endB): int
    {
        $start = $startA->greaterThan($startB) ? $startA : $startB;
        $end = $endA->lessThan($endB) ? $endA : $endB;

        return $start->greaterThan($end) ? 0 : $start->diffInDays($end) + 1;
    }

    /**
     * Same overlap as overlappingDays(), but as a set of 'Y-m-d' date strings
     * instead of a count, so callers can merge multiple unpaid-day sources
     * (ausencias, vacaciones y permisos sin goce) without double-counting a day
     * that falls in more than one of them at once.
     *
     * @return array<int, string>
     */
    private function overlappingDateSet($startA, $endA, $startB, $endB): array
    {
        $start = $startA->greaterThan($startB) ? $startA : $startB;
        $end = $endA->lessThan($endB) ? $endA : $endB;

        if ($start->greaterThan($end)) {
            return [];
        }

        $dates = [];
        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    protected function allocateNextCode(): string
    {
        return 'PLN-'.str_pad((string) ($this->lastPeriodNumber() + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function lastPeriodNumber(): int
    {
        $max = PayrollPeriod::query()
            ->where('code', 'like', 'PLN-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return (int) ($max ?? 0);
    }
}
