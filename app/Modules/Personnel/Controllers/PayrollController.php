<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
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
use App\Modules\Personnel\Models\TaxPolicy;
use App\Modules\Personnel\Requests\StorePayrollPeriodRequest;
use App\Modules\Personnel\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(): Response
    {
        $periods = PayrollPeriod::query()
            ->withCount('items')
            ->withSum('items', 'net_pay')
            ->latest('period_start')
            ->paginate(15)
            ->withQueryString();

        $vacations = EmployeeVacation::query()
            ->with('employee:id,full_name,employee_role_id')
            ->with('employee.role:id,name')
            ->latest('start_date')
            ->get();

        $bonuses = EmployeeBonus::query()
            ->with('employee:id,full_name,employee_role_id')
            ->with('employee.role:id,name')
            ->latest('bonus_date')
            ->get();

        $loans = EmployeeLoan::query()
            ->with('employee:id,full_name,employee_role_id')
            ->with('employee.role:id,name')
            ->latest('granted_at')
            ->get();

        $leaves = EmployeeLeave::query()
            ->with('employee:id,full_name,employee_role_id')
            ->with('employee.role:id,name')
            ->latest('start_date')
            ->get();

        $aguinaldos = AguinaldoPeriod::query()
            ->withCount('items')
            ->withSum('items', 'amount')
            ->latest('year')
            ->get();

        $deductions = EmployeeDeduction::query()
            ->with('employee:id,full_name,employee_role_id')
            ->with('employee.role:id,name')
            ->latest('deduction_date')
            ->get();

        $taxPolicies = TaxPolicy::query()
            ->orderByDesc('effective_from')
            ->get();

        $settlements = EmployeeSettlement::query()
            ->with('employee:id,full_name,employee_role_id')
            ->with('employee.role:id,name')
            ->latest('termination_date')
            ->get();

        return Inertia::render('Personnel/Payroll/Index', [
            'periods' => $periods,
            'vacations' => $vacations,
            'bonuses' => $bonuses,
            'loans' => $loans,
            'leaves' => $leaves,
            'deductions' => $deductions,
            'aguinaldos' => $aguinaldos,
            'settlements' => $settlements,
            'taxPolicies' => $taxPolicies,
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('full_name')
                ->get(['id', 'full_name']),
            'stats' => [
                'draft' => PayrollPeriod::query()->where('status', PayrollPeriod::STATUS_DRAFT)->count(),
                'approved' => PayrollPeriod::query()->where('status', PayrollPeriod::STATUS_APPROVED)->count(),
                'paid' => PayrollPeriod::query()->where('status', PayrollPeriod::STATUS_PAID)->count(),
                'eligible_employees' => Employee::query()
                    ->where('active', true)
                    ->whereNotNull('base_salary')
                    ->where('base_salary', '>', 0)
                    ->count(),
                'pending_vacations' => $vacations->where('status', EmployeeVacation::STATUS_PENDING)->count(),
                'pending_bonuses' => $bonuses->where('status', EmployeeBonus::STATUS_PENDING)->count(),
                'pending_bonus_total' => (float) $bonuses->where('status', EmployeeBonus::STATUS_PENDING)->sum('amount'),
                'active_loans' => $loans->where('status', EmployeeLoan::STATUS_ACTIVE)->count(),
                'active_loan_balance' => (float) $loans->where('status', EmployeeLoan::STATUS_ACTIVE)->sum('remaining_balance'),
                'pending_leaves' => $leaves->where('status', EmployeeLeave::STATUS_PENDING)->count(),
                'aguinaldo_years_generated' => $aguinaldos->count(),
                'aguinaldo_total' => (float) $aguinaldos->sum('items_sum_amount'),
                'pending_deductions' => $deductions->where('status', EmployeeDeduction::STATUS_PENDING)->count(),
                'pending_deduction_total' => (float) $deductions->where('status', EmployeeDeduction::STATUS_PENDING)->sum('amount'),
                'settlements_draft' => $settlements->where('status', EmployeeSettlement::STATUS_DRAFT)->count(),
                'settlements_approved' => $settlements->where('status', EmployeeSettlement::STATUS_APPROVED)->count(),
                'settlements_paid' => $settlements->where('status', EmployeeSettlement::STATUS_PAID)->count(),
                'settlements_paid_total' => (float) $settlements->where('status', EmployeeSettlement::STATUS_PAID)->sum('net_amount'),
            ],
            'currentYear' => (int) now()->year,
        ]);
    }

    public function store(StorePayrollPeriodRequest $request): RedirectResponse
    {
        $period = $this->payrollService->generatePeriod($request->validated());

        return redirect()
            ->route('payroll.show', $period)
            ->with('success', $period->items->isEmpty()
                ? 'Planilla creada, pero no hay colaboradores elegibles para esta frecuencia de pago.'
                : 'Planilla generada exitosamente.');
    }

    public function show(PayrollPeriod $payrollPeriod): Response
    {
        $payrollPeriod->load(['items.employee.role', 'taxPolicy']);

        return Inertia::render('Personnel/Payroll/Show', [
            'period' => $payrollPeriod,
            'totals' => [
                'gross' => (float) $payrollPeriod->items->sum('gross_salary'),
                'bonus' => (float) $payrollPeriod->items->sum('bonus_amount'),
                'deduction' => (float) $payrollPeriod->items->sum('deduction_amount'),
                'inss_employee' => (float) $payrollPeriod->items->sum('inss_employee'),
                'ir' => (float) $payrollPeriod->items->sum('ir_amount'),
                'loan_deduction' => (float) $payrollPeriod->items->sum('loan_deduction'),
                'net_pay' => (float) $payrollPeriod->items->sum('net_pay'),
                'inss_employer' => (float) $payrollPeriod->items->sum('inss_employer'),
                'inatec_employer' => (float) $payrollPeriod->items->sum('inatec_employer'),
            ],
        ]);
    }

    public function updateItem(Request $request, PayrollPeriod $payrollPeriod, PayrollItem $item): RedirectResponse
    {
        abort_if($item->payroll_period_id !== $payrollPeriod->id, 404);
        abort_if($payrollPeriod->status === PayrollPeriod::STATUS_PAID, 422, 'Una planilla ya pagada no se puede corregir.');

        $validated = $request->validate([
            'gross_salary' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'other_deductions' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $this->payrollService->recalculateItem($item, (float) $validated['gross_salary'], (float) $validated['other_deductions']);

        $wasApproved = $payrollPeriod->status === PayrollPeriod::STATUS_APPROVED;

        if ($wasApproved) {
            $payrollPeriod->update([
                'status' => PayrollPeriod::STATUS_DRAFT,
                'approved_at' => null,
            ]);
        }

        return redirect()
            ->route('payroll.show', $payrollPeriod)
            ->with('success', $wasApproved
                ? 'Línea corregida; la planilla volvió a borrador, vuelve a aprobarla antes de pagar.'
                : 'Línea de planilla corregida; INSS, IR y neto se recalcularon.');
    }

    public function approve(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_unless($this->payrollService->approve($payrollPeriod), 422, 'Solo se puede aprobar una planilla en borrador.');

        return redirect()
            ->route('payroll.show', $payrollPeriod)
            ->with('success', 'Planilla aprobada exitosamente.');
    }

    public function markPaid(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['efectivo', 'transferencia'])],
        ]);

        abort_unless(
            $this->payrollService->markPaid($payrollPeriod, $validated['payment_method']),
            422,
            'Solo se puede marcar como pagada una planilla ya aprobada.'
        );

        return redirect()
            ->route('payroll.show', $payrollPeriod)
            ->with('success', 'Planilla marcada como pagada.');
    }

    public function destroy(PayrollPeriod $payrollPeriod): RedirectResponse
    {
        abort_if($payrollPeriod->status !== PayrollPeriod::STATUS_DRAFT, 422, 'Solo se pueden eliminar planillas en borrador.');

        $payrollPeriod->delete();

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Planilla eliminada exitosamente.');
    }
}
