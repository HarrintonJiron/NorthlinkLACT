<?php

namespace App\Modules\Personnel\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Plant;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Requests\StoreEmployeeRequest;
use App\Modules\Personnel\Requests\UpdateEmployeeRequest;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly PersonnelService $personnelService) {}

    public function index(): Response
    {
        $employees = Employee::query()
            ->select([
                'id',
                'code',
                'employee_role_id',
                'first_name',
                'last_name',
                'identity_number',
                'email',
                'phone',
                'area',
                'plant_id',
                'hired_at',
                'status',
                'salary',
            ])
            ->with([
                'role:id,name',
                'plant:id,name,code',
            ])
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Employee $employee) => $this->personnelService->serializeEmployee($employee));

        $roles = EmployeeRole::query()
            ->select(['id', 'name', 'description', 'active'])
            ->withCount('employees')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();

        return Inertia::render('Personnel/Index', [
            'employees' => $employees,
            'roles' => $roles,
            'stats' => $this->personnelService->stats(),
            'plants' => Plant::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'statusOptions' => $this->personnelService->statusOptions(),
            'areaOptions' => $this->personnelService->areaOptions(),
            'contractTypeOptions' => $this->personnelService->contractTypeOptions(),
            'paymentMethodOptions' => $this->personnelService->paymentMethodOptions(),
        ]);
    }

    public function show(Employee $employee): Response
    {
        $employee->load([
            'role:id,name',
            'plant:id,name,code',
        ]);

        $attendances = $employee->attendances()
            ->latest('attendance_date')
            ->limit(50)
            ->get()
            ->map(fn ($attendance) => $this->personnelService->serializeAttendance($attendance));

        $deductions = $employee->deductions()
            ->where('type', '!=', PersonnelService::DEDUCTION_INSS)
            ->latest('deduction_date')
            ->limit(50)
            ->get()
            ->map(fn ($deduction) => $this->personnelService->serializeDeduction($deduction));

        $documents = $employee->documents()
            ->latest('id')
            ->get()
            ->map(fn ($document) => $this->personnelService->serializeDocument($document));

        return Inertia::render('Personnel/Show', [
            'employee' => $this->personnelService->serializeEmployee($employee),
            'attendances' => $attendances,
            'deductions' => $deductions,
            'documents' => $documents,
            'roles' => EmployeeRole::query()
                ->select(['id', 'name', 'active'])
                ->orderByDesc('active')
                ->orderBy('name')
                ->get(),
            'plants' => Plant::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'employees' => Employee::query()
                ->select(['id', 'code', 'first_name', 'last_name', 'employee_role_id', 'status'])
                ->with('role:id,name')
                ->orderBy('first_name')
                ->get()
                ->map(fn (Employee $item) => $this->personnelService->serializeEmployee($item)),
            'statusOptions' => $this->personnelService->statusOptions(),
            'areaOptions' => $this->personnelService->areaOptions(),
            'contractTypeOptions' => $this->personnelService->contractTypeOptions(),
            'paymentMethodOptions' => $this->personnelService->paymentMethodOptions(),
            'attendanceTypeOptions' => $this->personnelService->attendanceTypeOptions(),
            'deductionTypeOptions' => $this->personnelService->deductionTypeOptions(),
            'deductionStatusOptions' => $this->personnelService->deductionStatusOptions(),
            'documentTypeOptions' => $this->personnelService->documentTypeOptions(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::query()->create([
            ...$request->validated(),
            'code' => $this->personnelService->nextEmployeeCode(),
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Colaborador registrado correctamente.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Ficha del colaborador actualizada correctamente.');
    }

    public function updateStatus(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:activo,suspendido,retirado'],
        ]);

        $employee->update([
            'status' => $validated['status'],
        ]);

        $message = match ($validated['status']) {
            'activo' => 'Colaborador activado correctamente.',
            'suspendido' => 'Colaborador suspendido correctamente.',
            default => 'Colaborador marcado como retirado.',
        };

        return redirect()
            ->back()
            ->with('success', $message);
    }
}
