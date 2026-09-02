<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Personnel\Models\AguinaldoItem;
use App\Modules\Personnel\Models\Employee;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeHistoryController extends Controller
{
    public function show(Employee $employee): Response
    {
        $employee->load([
            'role',
            'vacations' => fn ($q) => $q->orderByDesc('start_date'),
            'leaves' => fn ($q) => $q->orderByDesc('start_date'),
            'bonuses' => fn ($q) => $q->orderByDesc('bonus_date'),
            'deductions' => fn ($q) => $q->orderByDesc('deduction_date'),
            'loans' => fn ($q) => $q->orderByDesc('granted_at'),
            'settlements' => fn ($q) => $q->orderByDesc('termination_date'),
            'payrollItems' => fn ($q) => $q->with('period:id,code,period_start,period_end,status')->orderByDesc('id'),
        ]);

        $aguinaldoItems = AguinaldoItem::query()
            ->where('employee_id', $employee->id)
            ->with('period:id,code,year,status')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Personnel/Payroll/EmployeeHistory', [
            'employee' => $employee,
            'aguinaldoItems' => $aguinaldoItems,
        ]);
    }
}
