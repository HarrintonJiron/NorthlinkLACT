<?php

namespace App\Modules\Personnel\Services;

use App\Modules\Personnel\Models\AguinaldoItem;
use App\Modules\Personnel\Models\AguinaldoPeriod;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeBonus;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeLeave;
use App\Modules\Personnel\Models\EmployeeLoan;
use App\Modules\Personnel\Models\EmployeeSettlement;
use App\Modules\Personnel\Models\EmployeeVacation;
use App\Modules\Personnel\Models\PayrollItem;
use App\Modules\Personnel\Models\PayrollPeriod;
use Carbon\Carbon;
use InvalidArgumentException;

class PayrollExportService
{
    public const SECTIONS = ['planillas', 'aguinaldo', 'vacations', 'leaves', 'bonuses', 'deductions', 'loans', 'settlements'];

    public const RANGES = ['day', 'week', 'month', 'year', 'custom'];

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveRange(string $range, ?string $date, ?string $start, ?string $end): array
    {
        if ($range === 'custom') {
            if (! $start || ! $end) {
                throw new InvalidArgumentException('Selecciona la fecha de inicio y fin del rango personalizado.');
            }

            return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()];
        }

        $anchor = $date ? Carbon::parse($date) : now();

        return match ($range) {
            'day' => [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()],
            'week' => [$anchor->copy()->startOfWeek(), $anchor->copy()->endOfWeek()],
            'month' => [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()],
            'year' => [$anchor->copy()->subMonths(11)->startOfMonth(), $anchor->copy()->endOfMonth()],
            default => throw new InvalidArgumentException('Rango inválido.'),
        };
    }

    /**
     * @return array{title: string, columns: array<int, string>, rows: array<int, array<int, string>>, totals: array<string, string>|null}
     */
    public function build(string $section, Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $data = match ($section) {
            'planillas' => $this->planillas($start, $end, $employee),
            'aguinaldo' => $this->aguinaldo($start, $end, $employee),
            'vacations' => $this->vacations($start, $end, $employee),
            'leaves' => $this->leaves($start, $end, $employee),
            'bonuses' => $this->bonuses($start, $end, $employee),
            'deductions' => $this->deductions($start, $end, $employee),
            'loans' => $this->loans($start, $end, $employee),
            'settlements' => $this->settlements($start, $end, $employee),
            default => throw new InvalidArgumentException('Sección inválida.'),
        };

        return $this->uppercaseColumns($data);
    }

    /**
     * Full itemized detail for a single planilla — every colaborador's line, not
     * just the period-level summary `build('planillas', ...)` returns.
     */
    public function planillaDetail(PayrollPeriod $period): array
    {
        $items = $period->items()->with('employee.role')->orderBy('id')->get();

        $rows = $items->map(fn ($item) => [
            $item->employee?->full_name ?? '—',
            $item->employee?->role?->name ?? '—',
            $this->money($item->gross_salary),
            $this->money($item->inss_employee),
            $this->money($item->ir_amount),
            (float) $item->loan_deduction > 0 ? $this->money($item->loan_deduction) : '—',
            (string) ($item->days_worked ?? '—'),
            $this->money($item->net_pay),
        ])->all();

        return $this->uppercaseColumns([
            'title' => 'Planilla '.$period->code,
            'columns' => ['Colaborador', 'Rol', 'Base + bono', 'INSS', 'IR', 'Préstamo', 'Días trab.', 'Neto'],
            'rows' => $rows,
            'totals' => [
                'Bruto total' => $this->money($items->sum('gross_salary')),
                'INSS total' => $this->money($items->sum('inss_employee')),
                'IR total' => $this->money($items->sum('ir_amount')),
                'Neto total' => $this->money($items->sum('net_pay')),
            ],
        ]);
    }

    /**
     * Full itemized detail for a single aguinaldo period.
     */
    public function aguinaldoDetail(AguinaldoPeriod $period): array
    {
        $items = $period->items()->with('employee.role')->orderBy('id')->get();

        $rows = $items->map(fn ($item) => [
            $item->employee?->full_name ?? '—',
            $item->employee?->role?->name ?? '—',
            $this->money($item->base_salary),
            $item->days_employed.' / 360',
            $this->money($item->amount),
        ])->all();

        return $this->uppercaseColumns([
            'title' => 'Aguinaldo '.$period->code.' · '.$period->year,
            'columns' => ['Colaborador', 'Rol', 'Sueldo base', 'Días', 'Aguinaldo'],
            'rows' => $rows,
            'totals' => ['Total' => $this->money($items->sum('amount'))],
        ]);
    }

    /**
     * Full breakdown for a single liquidación.
     */
    public function settlementDetail(EmployeeSettlement $settlement): array
    {
        $settlement->loadMissing('employee.role');

        $rows = [
            ['Salario pendiente', $this->money($settlement->pending_salary_amount)],
            ['Vacaciones proporcionales no gozadas', $this->money($settlement->vacation_amount)],
            ['Aguinaldo proporcional', $this->money($settlement->aguinaldo_amount)],
            ['Indemnización por antigüedad', $this->money($settlement->severance_amount)],
        ];

        if ((float) $settlement->loan_deduction > 0) {
            $rows[] = ['Saldo de préstamos activos', '-'.$this->money($settlement->loan_deduction)];
        }

        if ((float) $settlement->other_deduction > 0) {
            $rows[] = ['Deducciones pendientes', '-'.$this->money($settlement->other_deduction)];
        }

        return $this->uppercaseColumns([
            'title' => 'Liquidación '.$settlement->code.' · '.($settlement->employee?->full_name ?? ''),
            'columns' => ['Concepto', 'Monto'],
            'rows' => $rows,
            'totals' => ['Neto a pagar' => $this->money($settlement->net_amount)],
        ]);
    }

    /**
     * mb_strtoupper (not CSS text-transform) so accented characters render
     * correctly — dompdf mangles multi-byte UTF-8 through CSS uppercase.
     */
    private function uppercaseColumns(array $data): array
    {
        $data['columns'] = array_map(fn (string $c) => mb_strtoupper($c, 'UTF-8'), $data['columns']);

        return $data;
    }

    public function sectionTitle(string $section): string
    {
        return [
            'planillas' => 'Planillas',
            'aguinaldo' => 'Aguinaldo',
            'vacations' => 'Vacaciones',
            'leaves' => 'Permisos',
            'bonuses' => 'Bonos',
            'deductions' => 'Deducciones',
            'loans' => 'Préstamos',
            'settlements' => 'Liquidaciones',
        ][$section] ?? $section;
    }

    private function planillas(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        if ($employee) {
            $items = PayrollItem::query()
                ->where('employee_id', $employee->id)
                ->whereHas('period', fn ($q) => $q->where('period_start', '<=', $end)->where('period_end', '>=', $start))
                ->with('period')
                ->orderBy('id')
                ->get();

            $rows = $items->map(fn ($item) => [
                $item->period->code,
                $this->dateLabel($item->period->period_start).' – '.$this->dateLabel($item->period->period_end),
                $this->money($item->gross_salary),
                $this->money($item->net_pay),
                $this->periodStatusLabel($item->period->status),
            ])->all();

            return [
                'title' => 'Planillas',
                'columns' => ['Planilla', 'Periodo', 'Bruto', 'Neto', 'Estado'],
                'rows' => $rows,
                'totals' => ['Neto total' => $this->money($items->sum('net_pay'))],
            ];
        }

        $periods = PayrollPeriod::query()
            ->where('period_start', '<=', $end)
            ->where('period_end', '>=', $start)
            ->withCount('items')
            ->withSum('items', 'net_pay')
            ->orderBy('period_start')
            ->get();

        $rows = $periods->map(fn ($p) => [
            $p->code,
            $this->dateLabel($p->period_start).' – '.$this->dateLabel($p->period_end),
            (string) $p->items_count,
            $this->money($p->items_sum_net_pay ?? 0),
            $this->periodStatusLabel($p->status),
        ])->all();

        return [
            'title' => 'Planillas',
            'columns' => ['Planilla', 'Periodo', 'Colaboradores', 'Neto total', 'Estado'],
            'rows' => $rows,
            'totals' => ['Neto total' => $this->money($periods->sum('items_sum_net_pay'))],
        ];
    }

    private function aguinaldo(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        if ($employee) {
            $items = AguinaldoItem::query()
                ->where('employee_id', $employee->id)
                ->whereHas('period', fn ($q) => $q->where('period_start', '<=', $end)->where('period_end', '>=', $start))
                ->with('period')
                ->orderBy('id')
                ->get();

            $rows = $items->map(fn ($item) => [
                $item->period->code.' · '.$item->period->year,
                $item->days_employed.' / 360',
                $this->money($item->amount),
                $this->periodStatusLabel($item->period->status),
            ])->all();

            return [
                'title' => 'Aguinaldo',
                'columns' => ['Periodo', 'Días', 'Monto', 'Estado'],
                'rows' => $rows,
                'totals' => ['Monto total' => $this->money($items->sum('amount'))],
            ];
        }

        $periods = AguinaldoPeriod::query()
            ->where('period_start', '<=', $end)
            ->where('period_end', '>=', $start)
            ->withCount('items')
            ->withSum('items', 'amount')
            ->orderBy('period_start')
            ->get();

        $rows = $periods->map(fn ($p) => [
            $p->code.' · '.$p->year,
            (string) $p->items_count,
            $this->money($p->items_sum_amount ?? 0),
            $this->periodStatusLabel($p->status),
        ])->all();

        return [
            'title' => 'Aguinaldo',
            'columns' => ['Periodo', 'Colaboradores', 'Monto total', 'Estado'],
            'rows' => $rows,
            'totals' => ['Monto total' => $this->money($periods->sum('items_sum_amount'))],
        ];
    }

    private function vacations(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $query = EmployeeVacation::query()
            ->whereBetween('start_date', [$start, $end])
            ->orderBy('start_date');

        if ($employee) {
            $rows = $query->where('employee_id', $employee->id)->get()->map(fn ($v) => [
                $this->dateLabel($v->start_date),
                $this->dateLabel($v->end_date),
                (string) $v->days,
                $v->paid ? 'Con goce' : 'Sin goce',
                $this->approvalStatusLabel($v->status),
            ])->all();

            return ['title' => 'Vacaciones', 'columns' => ['Inicio', 'Fin', 'Días', 'Goce', 'Estado'], 'rows' => $rows, 'totals' => null];
        }

        $rows = $query->with('employee.role')->get()->map(fn ($v) => [
            $v->employee?->full_name ?? '—',
            $v->employee?->role?->name ?? '—',
            $this->dateLabel($v->start_date),
            $this->dateLabel($v->end_date),
            (string) $v->days,
            $v->paid ? 'Con goce' : 'Sin goce',
            $this->approvalStatusLabel($v->status),
        ])->all();

        return ['title' => 'Vacaciones', 'columns' => ['Colaborador', 'Rol', 'Inicio', 'Fin', 'Días', 'Goce', 'Estado'], 'rows' => $rows, 'totals' => null];
    }

    private function leaves(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $query = EmployeeLeave::query()
            ->whereBetween('start_date', [$start, $end])
            ->orderBy('start_date');

        if ($employee) {
            $rows = $query->where('employee_id', $employee->id)->get()->map(fn ($l) => [
                $this->leaveTypeLabel($l->type),
                $this->dateLabel($l->start_date),
                $this->dateLabel($l->end_date),
                (string) $l->days,
                $l->paid ? 'Con goce' : 'Sin goce',
                $this->approvalStatusLabel($l->status),
            ])->all();

            return ['title' => 'Permisos', 'columns' => ['Tipo', 'Inicio', 'Fin', 'Días', 'Goce', 'Estado'], 'rows' => $rows, 'totals' => null];
        }

        $rows = $query->with('employee.role')->get()->map(fn ($l) => [
            $l->employee?->full_name ?? '—',
            $l->employee?->role?->name ?? '—',
            $this->leaveTypeLabel($l->type),
            $this->dateLabel($l->start_date),
            $this->dateLabel($l->end_date),
            (string) $l->days,
            $l->paid ? 'Con goce' : 'Sin goce',
            $this->approvalStatusLabel($l->status),
        ])->all();

        return ['title' => 'Permisos', 'columns' => ['Colaborador', 'Rol', 'Tipo', 'Inicio', 'Fin', 'Días', 'Goce', 'Estado'], 'rows' => $rows, 'totals' => null];
    }

    private function bonuses(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $query = EmployeeBonus::query()
            ->whereBetween('bonus_date', [$start, $end])
            ->orderBy('bonus_date');

        if ($employee) {
            $records = $query->where('employee_id', $employee->id)->get();
            $rows = $records->map(fn ($b) => [
                $b->concept,
                $this->dateLabel($b->bonus_date),
                $this->money($b->amount),
                $this->applicationStatusLabel($b->status),
            ])->all();

            return ['title' => 'Bonos', 'columns' => ['Concepto', 'Fecha', 'Monto', 'Estado'], 'rows' => $rows, 'totals' => ['Total' => $this->money($records->sum('amount'))]];
        }

        $records = $query->with('employee.role')->get();
        $rows = $records->map(fn ($b) => [
            $b->employee?->full_name ?? '—',
            $b->employee?->role?->name ?? '—',
            $b->concept,
            $this->dateLabel($b->bonus_date),
            $this->money($b->amount),
            $this->applicationStatusLabel($b->status),
        ])->all();

        return ['title' => 'Bonos', 'columns' => ['Colaborador', 'Rol', 'Concepto', 'Fecha', 'Monto', 'Estado'], 'rows' => $rows, 'totals' => ['Total' => $this->money($records->sum('amount'))]];
    }

    private function deductions(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $query = EmployeeDeduction::query()
            ->whereBetween('deduction_date', [$start, $end])
            ->orderBy('deduction_date');

        if ($employee) {
            $records = $query->where('employee_id', $employee->id)->get();
            $rows = $records->map(fn ($d) => [
                $d->concept,
                $this->dateLabel($d->deduction_date),
                $this->money($d->amount),
                $this->applicationStatusLabel($d->status),
            ])->all();

            return ['title' => 'Deducciones', 'columns' => ['Concepto', 'Fecha', 'Monto', 'Estado'], 'rows' => $rows, 'totals' => ['Total' => $this->money($records->sum('amount'))]];
        }

        $records = $query->with('employee.role')->get();
        $rows = $records->map(fn ($d) => [
            $d->employee?->full_name ?? '—',
            $d->employee?->role?->name ?? '—',
            $d->concept,
            $this->dateLabel($d->deduction_date),
            $this->money($d->amount),
            $this->applicationStatusLabel($d->status),
        ])->all();

        return ['title' => 'Deducciones', 'columns' => ['Colaborador', 'Rol', 'Concepto', 'Fecha', 'Monto', 'Estado'], 'rows' => $rows, 'totals' => ['Total' => $this->money($records->sum('amount'))]];
    }

    private function loans(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $query = EmployeeLoan::query()
            ->whereBetween('granted_at', [$start, $end])
            ->orderBy('granted_at');

        if ($employee) {
            $records = $query->where('employee_id', $employee->id)->get();
            $rows = $records->map(fn ($l) => [
                $this->dateLabel($l->granted_at),
                $this->money($l->amount),
                $this->money($l->installment_amount),
                $this->money($l->remaining_balance),
                $l->status === 'active' ? 'Activo' : 'Pagado',
            ])->all();

            return ['title' => 'Préstamos', 'columns' => ['Otorgado', 'Monto', 'Cuota', 'Saldo', 'Estado'], 'rows' => $rows, 'totals' => ['Saldo pendiente' => $this->money($records->sum('remaining_balance'))]];
        }

        $records = $query->with('employee.role')->get();
        $rows = $records->map(fn ($l) => [
            $l->employee?->full_name ?? '—',
            $l->employee?->role?->name ?? '—',
            $this->dateLabel($l->granted_at),
            $this->money($l->amount),
            $this->money($l->remaining_balance),
            $l->status === 'active' ? 'Activo' : 'Pagado',
        ])->all();

        return ['title' => 'Préstamos', 'columns' => ['Colaborador', 'Rol', 'Otorgado', 'Monto', 'Saldo', 'Estado'], 'rows' => $rows, 'totals' => ['Saldo pendiente' => $this->money($records->sum('remaining_balance'))]];
    }

    private function settlements(Carbon $start, Carbon $end, ?Employee $employee): array
    {
        $query = EmployeeSettlement::query()
            ->whereBetween('termination_date', [$start, $end])
            ->orderBy('termination_date');

        if ($employee) {
            $records = $query->where('employee_id', $employee->id)->get();
            $rows = $records->map(fn ($s) => [
                $s->code,
                $this->terminationTypeLabel($s->termination_type),
                $this->dateLabel($s->termination_date),
                $this->money($s->net_amount),
                $this->periodStatusLabel($s->status),
            ])->all();

            return ['title' => 'Liquidaciones', 'columns' => ['Código', 'Tipo', 'Salida', 'Neto', 'Estado'], 'rows' => $rows, 'totals' => ['Neto total' => $this->money($records->sum('net_amount'))]];
        }

        $records = $query->with('employee.role')->get();
        $rows = $records->map(fn ($s) => [
            $s->employee?->full_name ?? '—',
            $s->employee?->role?->name ?? '—',
            $s->code,
            $this->terminationTypeLabel($s->termination_type),
            $this->dateLabel($s->termination_date),
            $this->money($s->net_amount),
            $this->periodStatusLabel($s->status),
        ])->all();

        return ['title' => 'Liquidaciones', 'columns' => ['Colaborador', 'Rol', 'Código', 'Tipo', 'Salida', 'Neto', 'Estado'], 'rows' => $rows, 'totals' => ['Neto total' => $this->money($records->sum('net_amount'))]];
    }

    private function money(float|string|null $value): string
    {
        return 'C$'.number_format((float) $value, 2);
    }

    private function dateLabel(?Carbon $date): string
    {
        return $date ? $date->format('d/m/Y') : '—';
    }

    private function periodStatusLabel(string $value): string
    {
        return ['draft' => 'Borrador', 'approved' => 'Aprobada', 'paid' => 'Pagada'][$value] ?? $value;
    }

    private function approvalStatusLabel(string $value): string
    {
        return ['pending' => 'Pendiente', 'approved' => 'Aprobada', 'rejected' => 'Rechazada'][$value] ?? $value;
    }

    private function applicationStatusLabel(string $value): string
    {
        return ['pending' => 'Pendiente', 'applied' => 'Aplicado'][$value] ?? $value;
    }

    private function terminationTypeLabel(string $value): string
    {
        return [
            'unjustified_dismissal' => 'Despido sin causa',
            'resignation' => 'Renuncia voluntaria',
            'justified_dismissal' => 'Despido con causa justificada',
            'mutual_agreement' => 'Mutuo acuerdo',
        ][$value] ?? $value;
    }

    private function leaveTypeLabel(string $value): string
    {
        return [
            'sick' => 'Enfermedad',
            'maternity' => 'Maternidad',
            'paternity' => 'Paternidad',
            'bereavement' => 'Duelo / fallecimiento familiar',
            'marriage' => 'Matrimonio',
            'legal' => 'Trámites legales',
            'unpaid_personal' => 'Personal sin goce de salario',
            'other' => 'Otro',
        ][$value] ?? $value;
    }
}
