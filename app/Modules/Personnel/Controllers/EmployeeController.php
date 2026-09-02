<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Requests\StoreEmployeeRequest;
use App\Modules\Personnel\Requests\UpdateEmployeeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(): Response
    {
        $employees = Employee::query()
            ->select([
                'id',
                'employee_role_id',
                'full_name',
                'identity_number',
                'email',
                'phone',
                'hired_at',
                'active',
                'base_salary',
                'pay_frequency',
                'created_at',
            ])
            ->with('role:id,name')
            ->withCount([
                'absences as month_absence_days' => fn ($query) => $query
                    ->where('type', 'unjustified')
                    ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]),
            ])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $elapsedDaysThisMonth = $today->day;

        $employees->getCollection()->transform(function (Employee $employee) use ($today, $monthStart, $elapsedDaysThisMonth) {
            $vacationDaysThisMonth = $employee->vacations()
                ->where('status', 'approved')
                ->get()
                ->sum(function ($vacation) use ($today, $monthStart) {
                    $start = $vacation->start_date->greaterThan($monthStart) ? $vacation->start_date : $monthStart;
                    $end = $vacation->end_date->lessThan($today) ? $vacation->end_date : $today;

                    return $start->greaterThan($end) ? 0 : $start->diffInDays($end) + 1;
                });

            $employee->tenure_days = $employee->tenureInDays();
            $employee->days_worked_this_month = max(0, $elapsedDaysThisMonth - $vacationDaysThisMonth - $employee->month_absence_days);

            return $employee;
        });

        $roles = EmployeeRole::query()
            ->select(['id', 'name', 'description', 'active'])
            ->withCount('employees')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Personnel/Index', [
            'employees' => $employees,
            'roles' => $roles,
            'stats' => [
                'total' => Employee::query()->count(),
                'active' => Employee::query()->where('active', true)->count(),
                'inactive' => Employee::query()->where('active', false)->count(),
                'roles' => EmployeeRole::query()->where('active', true)->count(),
            ],
        ]);
    }

    public function show(Employee $employee): Response
    {
        $employee->load([
            'role:id,name',
            'vacations' => fn ($query) => $query->latest('start_date'),
            'absences' => fn ($query) => $query->latest('date')->limit(30),
            'bonuses' => fn ($query) => $query->latest('bonus_date'),
            'loans' => fn ($query) => $query->latest('granted_at'),
            'payrollItems' => fn ($query) => $query->with('period:id,code,period_start,period_end,status')->latest('id')->limit(12),
        ]);

        $today = now();
        $monthStart = $today->copy()->startOfMonth();

        $vacationDaysThisMonth = $employee->vacations
            ->where('status', 'approved')
            ->sum(function ($vacation) use ($today, $monthStart) {
                $start = $vacation->start_date->greaterThan($monthStart) ? $vacation->start_date : $monthStart;
                $end = $vacation->end_date->lessThan($today) ? $vacation->end_date : $today;

                return $start->greaterThan($end) ? 0 : $start->diffInDays($end) + 1;
            });

        $absenceDaysThisMonth = $employee->absences
            ->where('type', 'unjustified')
            ->filter(fn ($absence) => $absence->date->greaterThanOrEqualTo($monthStart))
            ->count();

        return Inertia::render('Personnel/Employees/Show', [
            'employee' => $employee,
            'tenureDays' => $employee->tenureInDays(),
            'daysWorkedThisMonth' => max(0, $today->day - $vacationDaysThisMonth - $absenceDaysThisMonth),
            'vacationBalance' => $employee->vacationBalance(),
            'activeLoanBalance' => (float) $employee->loans->where('status', 'active')->sum('remaining_balance'),
            'pendingBonusTotal' => (float) $employee->bonuses->where('status', 'pending')->sum('amount'),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::query()->create($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Colaborador creado exitosamente.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('success', 'Colaborador actualizado exitosamente.');
    }

    public function updateStatus(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $employee->update([
            'active' => $validated['active'],
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', $employee->active
                ? 'Colaborador activado exitosamente.'
                : 'Colaborador desactivado exitosamente.');
    }
}
