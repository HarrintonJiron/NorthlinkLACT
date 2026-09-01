<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_page_renders_active_and_inactive_roles_with_totals(): void
    {
        $administrativeRole = EmployeeRole::factory()->create(['name' => 'Contabilidad']);
        $routeRole = EmployeeRole::factory()->create(['name' => 'Acopiador', 'active' => false]);
        Employee::factory()->for($administrativeRole, 'role')->create(['first_name' => 'Ana', 'last_name' => 'Pérez']);
        Employee::factory()->for($routeRole, 'role')->create(['first_name' => 'Carlos', 'last_name' => 'López']);

        $this->get(route('employees.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Personnel/Index')
                ->has('employees.data', 2)
                ->has('roles', 2)
                ->where('stats.total', 2)
                ->where('stats.active', 2));
    }

    public function test_valid_payload_creates_a_collaborator_and_redirects_to_profile(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);

        $response = $this->post(route('employees.store'), [
            'first_name' => 'María',
            'last_name' => 'González',
            'employee_role_id' => $role->id,
            'identity_number' => '001-010190-0001A',
            'email' => 'MARIA@EXAMPLE.COM',
            'phone' => '8888-7777',
            'hired_at' => '2026-08-01',
            'status' => 'activo',
            'inss_insured' => true,
        ]);

        $employee = Employee::query()->firstOrFail();

        $response
            ->assertRedirect(route('employees.show', $employee))
            ->assertSessionHas('success');

        $this->assertSame('María', $employee->first_name);
        $this->assertSame('González', $employee->last_name);
        $this->assertSame('María González', $employee->full_name);
        $this->assertSame('maria@example.com', $employee->email);
        $this->assertStringStartsWith('EMP-', $employee->code);
        $this->assertSame('activo', $employee->status);
    }

    public function test_employee_profile_page_renders_ficha_sections(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Luis',
            'last_name' => 'Ramírez',
            'area' => 'Acopio',
        ]);

        $this->get(route('employees.show', $employee))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Personnel/Show')
                ->where('employee.full_name', 'Luis Ramírez')
                ->where('employee.area', 'Acopio')
                ->has('attendances')
                ->has('deductions')
                ->has('documents'));
    }

    public function test_can_register_attendance_and_deduction_from_employee_profile(): void
    {
        Storage::fake('public');
        $employee = Employee::factory()->create();

        $this->post(route('employees.employee.attendances.store', $employee), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-15',
            'type' => EmployeeAttendance::TYPE_PRESENTE,
            'check_in' => '07:30',
            'check_out' => '16:00',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $employee->id,
            'type' => EmployeeAttendance::TYPE_PRESENTE,
        ]);

        $this->post(route('employees.employee.deductions.store', $employee), [
            'employee_id' => $employee->id,
            'type' => 'prestamo',
            'amount' => 1500,
            'total_amount' => 15000,
            'installment_amount' => 1500,
            'installments_total' => 10,
            'deduction_date' => '2026-08-15',
            'status' => 'activa',
            'reason' => 'Préstamo personal',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_deductions', [
            'employee_id' => $employee->id,
            'type' => 'prestamo',
            'installments_total' => 10,
        ]);

        $this->post(route('employees.employee.deductions.store', $employee), [
            'employee_id' => $employee->id,
            'type' => 'adelanto_salario',
            'amount' => 2000,
            'total_amount' => 6000,
            'installment_amount' => 2000,
            'installments_total' => 3,
            'deduction_date' => '2026-08-20',
            'status' => 'activa',
            'reason' => 'Adelanto quincenal',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_deductions', [
            'employee_id' => $employee->id,
            'type' => 'adelanto_salario',
            'installments_total' => 3,
        ]);
    }

    public function test_can_upload_document_to_employee_profile(): void
    {
        Storage::fake('public');
        $employee = Employee::factory()->create();

        $this->post(route('employees.documents.store', $employee), [
            'name' => 'Contrato laboral',
            'type' => 'contrato',
            'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'name' => 'Contrato laboral',
            'type' => 'contrato',
        ]);
    }

    public function test_status_update_accepts_valid_status_values(): void
    {
        $employee = Employee::factory()->create(['status' => 'activo']);

        $this->patch(route('employees.status.update', $employee), ['status' => 'suspendido'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('suspendido', $employee->fresh()->status);
    }

    public function test_insured_employee_includes_automatic_inss_deduction_on_profile(): void
    {
        $employee = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => 16000,
        ]);

        $this->get(route('employees.show', $employee))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Personnel/Show')
                ->where('employee.inss_deduction.amount', 1120)
                ->where('employee.inss_deduction.rate_label', '7%')
                ->where('employee.inss_deduction.automatic', true)
                ->where('deductionTypeOptions', fn ($options) => ! collect($options)->contains('value', 'inss')));
    }

    public function test_manual_inss_deduction_is_rejected(): void
    {
        $employee = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => 10000,
        ]);

        $this->from(route('employees.show', $employee))
            ->post(route('employees.employee.deductions.store', $employee), [
                'employee_id' => $employee->id,
                'type' => 'inss',
                'amount' => 700,
                'deduction_date' => '2026-08-15',
                'status' => 'activa',
            ])
            ->assertRedirect(route('employees.show', $employee))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('employee_deductions', [
            'employee_id' => $employee->id,
            'type' => 'inss',
        ]);
    }
}
