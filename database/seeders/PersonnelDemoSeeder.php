<?php

namespace Database\Seeders;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Database\Seeder;

class PersonnelDemoSeeder extends Seeder
{
    /**
     * Datos de prueba del módulo Personal para desarrollo y demos.
     */
    public function run(): void
    {
        $this->call(EmployeeRoleSeeder::class);

        $adminRole = EmployeeRole::query()->where('name', 'Administrativo')->firstOrFail();
        $routeRole = EmployeeRole::query()->where('name', 'Ruta')->firstOrFail();
        $service = app(PersonnelService::class);

        $maria = Employee::query()->updateOrCreate(
            ['code' => 'EMP-0001'],
            [
                'identity_number' => '001-150890-0001A',
                'employee_role_id' => $adminRole->id,
                'first_name' => 'María',
                'last_name' => 'González',
                'area' => 'Administración',
                'email' => 'maria.gonzalez@northlink.test',
                'phone' => '8888-1001',
                'hired_at' => '2024-03-01',
                'status' => PersonnelService::STATUS_ACTIVO,
                'contract_type' => PersonnelService::CONTRACT_INDEFINIDO,
                'contract_start_date' => '2024-03-01',
                'salary' => 18500,
                'inss_insured' => true,
                'inss_number' => 'INSS-458712',
                'payment_method' => PersonnelService::PAYMENT_TRANSFERENCIA,
                'bank_account' => '1234567890123456',
                'emergency_contact_name' => 'Carlos González',
                'emergency_contact_phone' => '8888-2001',
            ],
        );

        $carlos = Employee::query()->updateOrCreate(
            ['code' => 'EMP-0002'],
            [
                'identity_number' => '001-220795-0002B',
                'employee_role_id' => $routeRole->id,
                'first_name' => 'Carlos',
                'last_name' => 'Ramírez',
                'area' => 'Acopio',
                'email' => 'carlos.ramirez@northlink.test',
                'phone' => '8888-1002',
                'hired_at' => '2023-11-15',
                'status' => PersonnelService::STATUS_ACTIVO,
                'contract_type' => PersonnelService::CONTRACT_INDEFINIDO,
                'contract_start_date' => '2023-11-15',
                'salary' => 14200,
                'inss_insured' => true,
                'inss_number' => 'INSS-991234',
                'payment_method' => PersonnelService::PAYMENT_EFECTIVO,
                'emergency_contact_name' => 'Ana Ramírez',
                'emergency_contact_phone' => '8888-2002',
            ],
        );

        $laura = Employee::query()->updateOrCreate(
            ['code' => 'EMP-0003'],
            [
                'identity_number' => '001-080992-0003C',
                'employee_role_id' => $adminRole->id,
                'first_name' => 'Laura',
                'last_name' => 'Martínez',
                'area' => 'Recursos Humanos',
                'email' => 'laura.martinez@northlink.test',
                'phone' => '8888-1003',
                'hired_at' => '2025-01-10',
                'status' => PersonnelService::STATUS_SUSPENDIDO,
                'contract_type' => PersonnelService::CONTRACT_TEMPORAL,
                'contract_start_date' => '2025-01-10',
                'contract_end_date' => '2026-01-09',
                'salary' => 12000,
                'inss_insured' => false,
                'payment_method' => PersonnelService::PAYMENT_TRANSFERENCIA,
            ],
        );

        Employee::query()->updateOrCreate(
            ['code' => 'EMP-0004'],
            [
                'identity_number' => '001-301087-0004D',
                'employee_role_id' => $routeRole->id,
                'first_name' => 'José',
                'last_name' => 'Herrera',
                'area' => 'Logística',
                'email' => 'jose.herrera@northlink.test',
                'phone' => '8888-1004',
                'hired_at' => '2022-06-01',
                'status' => PersonnelService::STATUS_RETIRADO,
                'contract_type' => PersonnelService::CONTRACT_INDEFINIDO,
                'salary' => 9800,
                'inss_insured' => true,
                'inss_number' => 'INSS-772211',
            ],
        );

        foreach ([$maria, $carlos] as $employee) {
            EmployeeAttendance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => now()->subDays(2)->toDateString(),
                ],
                [
                    'type' => EmployeeAttendance::TYPE_PRESENTE,
                    'check_in' => '07:15',
                    'check_out' => '16:00',
                ],
            );

            EmployeeAttendance::query()->updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'attendance_date' => now()->subDay()->toDateString(),
                ],
                [
                    'type' => EmployeeAttendance::TYPE_PERMISO,
                    'notes' => 'Permiso personal autorizado',
                ],
            );
        }

        EmployeeDeduction::query()->updateOrCreate(
            [
                'employee_id' => $carlos->id,
                'type' => PersonnelService::DEDUCTION_ADELANTO_SALARIO,
                'deduction_date' => now()->startOfMonth()->toDateString(),
            ],
            [
                'amount' => 2000,
                'total_amount' => 6000,
                'installment_amount' => 2000,
                'installments_total' => 3,
                'installments_paid' => 1,
                'status' => PersonnelService::DEDUCTION_STATUS_ACTIVA,
                'reason' => 'Adelanto quincenal agosto',
            ],
        );

        EmployeeDeduction::query()->updateOrCreate(
            [
                'employee_id' => $maria->id,
                'type' => PersonnelService::DEDUCTION_PRESTAMO,
                'deduction_date' => '2026-06-01',
            ],
            [
                'amount' => 1500,
                'total_amount' => 15000,
                'installment_amount' => 1500,
                'installments_total' => 10,
                'installments_paid' => 2,
                'status' => PersonnelService::DEDUCTION_STATUS_ACTIVA,
                'reason' => 'Préstamo personal',
            ],
        );

        EmployeeDeduction::query()->updateOrCreate(
            [
                'employee_id' => $laura->id,
                'type' => PersonnelService::DEDUCTION_AUSENCIA,
                'deduction_date' => now()->startOfMonth()->toDateString(),
            ],
            [
                'amount' => 400,
                'status' => PersonnelService::DEDUCTION_STATUS_ACTIVA,
                'reason' => 'Descuento por ausencia injustificada',
            ],
        );

        $this->command?->info('Personal demo: 4 colaboradores, asistencias y deducciones registradas.');
        $this->command?->info('INSS automático María: C$'.number_format($service->inssDeductionForEmployee($maria)['amount'] ?? 0, 2));
        $this->command?->info('INSS automático Carlos: C$'.number_format($service->inssDeductionForEmployee($carlos)['amount'] ?? 0, 2));
    }
}
