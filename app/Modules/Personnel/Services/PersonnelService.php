<?php

namespace App\Modules\Personnel\Services;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeDocument;
use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Support\Facades\DB;

class PersonnelService
{
    public const STATUS_ACTIVO = 'activo';

    public const STATUS_SUSPENDIDO = 'suspendido';

    public const STATUS_RETIRADO = 'retirado';

    public const CONTRACT_INDEFINIDO = 'indefinido';

    public const CONTRACT_TEMPORAL = 'temporal';

    public const CONTRACT_OBRA = 'por_obra';

    public const CONTRACT_PRACTICAS = 'practicas';

    public const PAYMENT_EFECTIVO = 'efectivo';

    public const PAYMENT_TRANSFERENCIA = 'transferencia';

    public const PAYMENT_CHEQUE = 'cheque';

    public const DEDUCTION_INSS = 'inss';

    public const INSS_EMPLOYEE_RATE = 0.07;

    public const DEDUCTION_ADELANTO_SALARIO = 'adelanto_salario';

    public const DEDUCTION_PRESTAMO = 'prestamo';

    public const DEDUCTION_AUSENCIA = 'ausencia';

    public const DEDUCTION_OTRA = 'otra';

    public const DEDUCTION_STATUS_ACTIVA = 'activa';

    public const DEDUCTION_STATUS_COMPLETADA = 'completada';

    public const DEDUCTION_STATUS_CANCELADA = 'cancelada';

    public const DOCUMENT_CONTRATO = 'contrato';

    public const DOCUMENT_IDENTIFICACION = 'identificacion';

    public const DOCUMENT_CONSTANCIA = 'constancia';

    public const DOCUMENT_OTRO = 'otro';

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function statusOptions(): array
    {
        return [
            ['value' => self::STATUS_ACTIVO, 'label' => 'Activo'],
            ['value' => self::STATUS_SUSPENDIDO, 'label' => 'Suspendido'],
            ['value' => self::STATUS_RETIRADO, 'label' => 'Retirado'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function areaOptions(): array
    {
        return collect([
            'Administración',
            'Producción',
            'Logística',
            'Acopio',
            'Calidad',
            'Recursos Humanos',
            'Finanzas',
            'Mantenimiento',
        ])->map(fn (string $area) => ['value' => $area, 'label' => $area])->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function contractTypeOptions(): array
    {
        return [
            ['value' => self::CONTRACT_INDEFINIDO, 'label' => 'Indefinido'],
            ['value' => self::CONTRACT_TEMPORAL, 'label' => 'Temporal'],
            ['value' => self::CONTRACT_OBRA, 'label' => 'Por obra'],
            ['value' => self::CONTRACT_PRACTICAS, 'label' => 'Prácticas'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function paymentMethodOptions(): array
    {
        return [
            ['value' => self::PAYMENT_EFECTIVO, 'label' => 'Efectivo'],
            ['value' => self::PAYMENT_TRANSFERENCIA, 'label' => 'Transferencia bancaria'],
            ['value' => self::PAYMENT_CHEQUE, 'label' => 'Cheque'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function deductionTypeOptions(): array
    {
        return [
            ['value' => self::DEDUCTION_ADELANTO_SALARIO, 'label' => 'Adelanto de salario'],
            ['value' => self::DEDUCTION_PRESTAMO, 'label' => 'Préstamos'],
            ['value' => self::DEDUCTION_AUSENCIA, 'label' => 'Ausencias'],
            ['value' => self::DEDUCTION_OTRA, 'label' => 'Otras deducciones'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function inssDeductionForEmployee(Employee $employee): ?array
    {
        if (! $employee->inss_insured || $employee->salary === null || (float) $employee->salary <= 0) {
            return null;
        }

        $salary = (float) $employee->salary;
        $amount = round($salary * self::INSS_EMPLOYEE_RATE, 2);

        return [
            'type' => self::DEDUCTION_INSS,
            'type_label' => 'INSS',
            'amount' => $amount,
            'rate' => self::INSS_EMPLOYEE_RATE,
            'rate_label' => '7%',
            'salary_base' => $salary,
            'automatic' => true,
            'reason' => 'Aporte laboral al INSS (7% del salario bruto)',
            'status' => self::DEDUCTION_STATUS_ACTIVA,
            'status_label' => $this->deductionStatusLabel(self::DEDUCTION_STATUS_ACTIVA),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function deductionTypesWithInstallments(): array
    {
        return [
            self::DEDUCTION_ADELANTO_SALARIO,
            self::DEDUCTION_PRESTAMO,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function deductionStatusOptions(): array
    {
        return [
            ['value' => self::DEDUCTION_STATUS_ACTIVA, 'label' => 'Activa'],
            ['value' => self::DEDUCTION_STATUS_COMPLETADA, 'label' => 'Completada'],
            ['value' => self::DEDUCTION_STATUS_CANCELADA, 'label' => 'Cancelada'],
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function attendanceTypeOptions(): array
    {
        return collect(EmployeeAttendance::types())->map(fn (string $type) => [
            'value' => $type,
            'label' => EmployeeAttendance::typeLabel($type),
        ])->values()->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function documentTypeOptions(): array
    {
        return [
            ['value' => self::DOCUMENT_CONTRATO, 'label' => 'Contrato'],
            ['value' => self::DOCUMENT_IDENTIFICACION, 'label' => 'Identificación'],
            ['value' => self::DOCUMENT_CONSTANCIA, 'label' => 'Constancia'],
            ['value' => self::DOCUMENT_OTRO, 'label' => 'Otro'],
        ];
    }

    public function nextEmployeeCode(): string
    {
        return DB::transaction(function (): string {
            $lastNumber = Employee::query()
                ->withTrashed()
                ->where('code', 'like', 'EMP-%')
                ->lockForUpdate()
                ->get(['code'])
                ->map(fn (Employee $employee) => (int) substr((string) $employee->code, 4))
                ->max() ?? 0;

            return 'EMP-'.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        });
    }

    public function statusLabel(string $status): string
    {
        return collect($this->statusOptions())->firstWhere('value', $status)['label'] ?? $status;
    }

    public function contractTypeLabel(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        return collect($this->contractTypeOptions())->firstWhere('value', $type)['label'] ?? $type;
    }

    public function paymentMethodLabel(?string $method): ?string
    {
        if ($method === null) {
            return null;
        }

        return collect($this->paymentMethodOptions())->firstWhere('value', $method)['label'] ?? $method;
    }

    public function deductionTypeLabel(string $type): string
    {
        if ($type === self::DEDUCTION_INSS) {
            return 'INSS';
        }

        if ($type === 'anticipo') {
            return 'Adelanto de salario';
        }

        return collect($this->deductionTypeOptions())->firstWhere('value', $type)['label'] ?? $type;
    }

    public function deductionStatusLabel(string $status): string
    {
        return collect($this->deductionStatusOptions())->firstWhere('value', $status)['label'] ?? $status;
    }

    public function documentTypeLabel(string $type): string
    {
        return collect($this->documentTypeOptions())->firstWhere('value', $type)['label'] ?? $type;
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $total = Employee::query()->count();
        $active = Employee::query()->where('status', self::STATUS_ACTIVO)->count();
        $suspended = Employee::query()->where('status', self::STATUS_SUSPENDIDO)->count();
        $retired = Employee::query()->where('status', self::STATUS_RETIRADO)->count();
        $inssInsured = Employee::query()->where('inss_insured', true)->count();
        $rolesCount = EmployeeRole::query()->where('active', true)->count();
        $newThisMonth = Employee::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $monthLabels = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $monthly = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $monthly->push([
                'key' => $date->format('Y-m'),
                'label' => $monthLabels[(int) $date->format('n')],
                'count' => Employee::query()
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ]);
        }

        $byRole = EmployeeRole::query()
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->orderBy('name')
            ->get(['id', 'name', 'active'])
            ->map(fn (EmployeeRole $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'count' => $role->employees_count,
                'active' => $role->active,
            ])
            ->values()
            ->all();

        return [
            'total' => $total,
            'active' => $active,
            'suspended' => $suspended,
            'retired' => $retired,
            'inactive' => $suspended + $retired,
            'roles' => $rolesCount,
            'inss_insured' => $inssInsured,
            'new_this_month' => $newThisMonth,
            'monthly' => $monthly->all(),
            'by_role' => $byRole,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeEmployee(Employee $employee): array
    {
        $employee->loadMissing(['role:id,name', 'plant:id,name,code']);

        return [
            'id' => $employee->id,
            'code' => $employee->code,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'full_name' => $employee->full_name,
            'identity_number' => $employee->identity_number,
            'employee_role_id' => $employee->employee_role_id,
            'role' => $employee->role,
            'area' => $employee->area,
            'plant_id' => $employee->plant_id,
            'plant' => $employee->plant,
            'hired_at' => $employee->hired_at?->toDateString(),
            'status' => $employee->status,
            'status_label' => $this->statusLabel($employee->status),
            'contract_type' => $employee->contract_type,
            'contract_type_label' => $this->contractTypeLabel($employee->contract_type),
            'contract_start_date' => $employee->contract_start_date?->toDateString(),
            'contract_end_date' => $employee->contract_end_date?->toDateString(),
            'salary' => $employee->salary !== null ? (float) $employee->salary : null,
            'inss_insured' => $employee->inss_insured,
            'inss_number' => $employee->inss_number,
            'inss_deduction' => $this->inssDeductionForEmployee($employee),
            'payment_method' => $employee->payment_method,
            'payment_method_label' => $this->paymentMethodLabel($employee->payment_method),
            'bank_account' => $employee->bank_account,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'address' => $employee->address,
            'emergency_contact_name' => $employee->emergency_contact_name,
            'emergency_contact_phone' => $employee->emergency_contact_phone,
            'created_at' => $employee->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeAttendance(EmployeeAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'attendance_date' => $attendance->attendance_date->toDateString(),
            'type' => $attendance->type,
            'type_label' => EmployeeAttendance::typeLabel($attendance->type),
            'check_in' => $attendance->check_in,
            'check_out' => $attendance->check_out,
            'notes' => $attendance->notes,
            'has_justification' => $attendance->justification_path !== null,
            'justification_name' => $attendance->justification_name,
            'created_at' => $attendance->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDeduction(EmployeeDeduction $deduction): array
    {
        return [
            'id' => $deduction->id,
            'employee_id' => $deduction->employee_id,
            'type' => $deduction->type,
            'type_label' => $this->deductionTypeLabel($deduction->type),
            'amount' => (float) $deduction->amount,
            'total_amount' => $deduction->total_amount !== null ? (float) $deduction->total_amount : null,
            'installment_amount' => $deduction->installment_amount !== null ? (float) $deduction->installment_amount : null,
            'installments_total' => $deduction->installments_total,
            'installments_paid' => $deduction->installments_paid,
            'installments_pending' => $deduction->installmentsPending(),
            'deduction_date' => $deduction->deduction_date->toDateString(),
            'reason' => $deduction->reason,
            'status' => $deduction->status,
            'status_label' => $this->deductionStatusLabel($deduction->status),
            'created_at' => $deduction->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeDocument(EmployeeDocument $document): array
    {
        return [
            'id' => $document->id,
            'employee_id' => $document->employee_id,
            'name' => $document->name,
            'type' => $document->type,
            'type_label' => $this->documentTypeLabel($document->type),
            'file_name' => $document->file_name,
            'file_size' => $document->file_size,
            'notes' => $document->notes,
            'download_url' => route('employees.documents.download', [$document->employee_id, $document->id]),
            'created_at' => $document->created_at?->toIso8601String(),
        ];
    }
}
