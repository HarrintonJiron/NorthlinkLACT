<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAttendance;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeDocument;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PersonnelModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticate();
    }

    public function test_employee_update_persists_profile_changes(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);
        $employee = Employee::factory()->for($role, 'role')->create([
            'first_name' => 'Pedro',
            'last_name' => 'Mora',
            'salary' => 12000,
            'inss_insured' => false,
        ]);

        $this->put(route('employees.update', $employee), [
            'first_name' => 'Pedro',
            'last_name' => 'Morales',
            'employee_role_id' => $role->id,
            'status' => 'activo',
            'salary' => 18000,
            'inss_insured' => true,
            'inss_number' => 'INSS-12345',
            'contract_type' => 'indefinido',
            'payment_method' => 'transferencia',
            'area' => 'Producción',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();

        $this->assertSame('Morales', $employee->last_name);
        $this->assertSame(18000.0, (float) $employee->salary);
        $this->assertTrue($employee->inss_insured);
        $this->assertSame('INSS-12345', $employee->inss_number);
    }

    public function test_store_rejects_duplicate_email_and_identity_number(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);
        Employee::factory()->for($role, 'role')->create([
            'email' => 'duplicado@example.com',
            'identity_number' => '001-010190-0001A',
        ]);

        $payload = [
            'first_name' => 'Nuevo',
            'last_name' => 'Colaborador',
            'employee_role_id' => $role->id,
            'status' => 'activo',
            'inss_insured' => false,
        ];

        $this->post(route('employees.store'), [
            ...$payload,
            'email' => 'duplicado@example.com',
        ])->assertSessionHasErrors('email');

        $this->post(route('employees.store'), [
            ...$payload,
            'identity_number' => '001-010190-0001A',
        ])->assertSessionHasErrors('identity_number');
    }

    public function test_store_rejects_inactive_role(): void
    {
        $inactiveRole = EmployeeRole::factory()->create(['active' => false]);

        $this->post(route('employees.store'), [
            'first_name' => 'Ana',
            'last_name' => 'López',
            'employee_role_id' => $inactiveRole->id,
            'status' => 'activo',
            'inss_insured' => false,
        ])->assertSessionHasErrors('employee_role_id');
    }

    public function test_update_allows_keeping_an_inactive_role_already_assigned(): void
    {
        $inactiveRole = EmployeeRole::factory()->create(['active' => false]);
        $employee = Employee::factory()->for($inactiveRole, 'role')->create([
            'first_name' => 'Laura',
            'last_name' => 'Ruiz',
        ]);

        $this->put(route('employees.update', $employee), [
            'first_name' => 'Laura',
            'last_name' => 'Ruiz Pérez',
            'employee_role_id' => $inactiveRole->id,
            'status' => 'activo',
            'inss_insured' => false,
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertSame('Ruiz Pérez', $employee->fresh()->last_name);
    }

    public function test_status_update_rejects_invalid_status(): void
    {
        $employee = Employee::factory()->create(['status' => 'activo']);

        $this->patch(route('employees.status.update', $employee), ['status' => 'invalido'])
            ->assertSessionHasErrors('status');

        $this->assertSame('activo', $employee->fresh()->status);
    }

    public function test_insured_employee_without_salary_has_no_automatic_inss_on_profile(): void
    {
        $employee = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => null,
        ]);

        $this->get(route('employees.show', $employee))
            ->assertInertia(fn (Assert $page) => $page
                ->where('employee.inss_deduction', null));
    }

    public function test_legacy_inss_deduction_records_are_hidden_from_manual_list(): void
    {
        $employee = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => 10000,
        ]);

        EmployeeDeduction::query()->create([
            'employee_id' => $employee->id,
            'type' => 'inss',
            'amount' => 500,
            'deduction_date' => '2026-08-01',
            'status' => 'activa',
        ]);

        EmployeeDeduction::query()->create([
            'employee_id' => $employee->id,
            'type' => 'otra',
            'amount' => 250,
            'deduction_date' => '2026-08-02',
            'status' => 'activa',
            'reason' => 'Multa',
        ]);

        $this->get(route('employees.show', $employee))
            ->assertInertia(fn (Assert $page) => $page
                ->has('deductions', 1)
                ->where('deductions.0.type', 'otra')
                ->where('employee.inss_deduction.amount', 700));
    }

    public function test_attendance_upserts_record_for_same_date(): void
    {
        $employee = Employee::factory()->create();

        $this->post(route('employees.employee.attendances.store', $employee), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-15',
            'type' => EmployeeAttendance::TYPE_PRESENTE,
            'check_in' => '07:30',
            'check_out' => '16:00',
        ])->assertRedirect();

        $this->post(route('employees.employee.attendances.store', $employee), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-15',
            'type' => EmployeeAttendance::TYPE_AUSENTE,
            'notes' => 'Justificado por permiso',
        ])->assertRedirect();

        $this->assertSame(1, EmployeeAttendance::query()->where('employee_id', $employee->id)->count());

        $attendance = EmployeeAttendance::query()->where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(EmployeeAttendance::TYPE_AUSENTE, $attendance->type);
        $this->assertSame('2026-08-15', $attendance->attendance_date->toDateString());
        $this->assertNull($attendance->check_in);
        $this->assertNull($attendance->check_out);
    }

    public function test_presente_attendance_requires_check_in_and_valid_check_out(): void
    {
        $employee = Employee::factory()->create();

        $this->from(route('employees.show', $employee))
            ->post(route('employees.employee.attendances.store', $employee), [
                'employee_id' => $employee->id,
                'attendance_date' => '2026-08-15',
                'type' => EmployeeAttendance::TYPE_PRESENTE,
            ])
            ->assertSessionHasErrors('check_in');

        $this->from(route('employees.show', $employee))
            ->post(route('employees.employee.attendances.store', $employee), [
                'employee_id' => $employee->id,
                'attendance_date' => '2026-08-15',
                'type' => EmployeeAttendance::TYPE_PRESENTE,
                'check_in' => '16:00',
                'check_out' => '07:30',
            ])
            ->assertSessionHasErrors('check_out');
    }

    public function test_ausente_attendance_does_not_require_check_in(): void
    {
        $employee = Employee::factory()->create();

        $this->post(route('employees.employee.attendances.store', $employee), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-16',
            'type' => EmployeeAttendance::TYPE_AUSENTE,
            'notes' => 'Sin justificación',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $employee->id,
            'type' => EmployeeAttendance::TYPE_AUSENTE,
        ]);
    }

    public function test_prestamo_deduction_requires_installment_fields(): void
    {
        $employee = Employee::factory()->create();

        $this->from(route('employees.show', $employee))
            ->post(route('employees.employee.deductions.store', $employee), [
                'employee_id' => $employee->id,
                'type' => 'prestamo',
                'amount' => 1000,
                'deduction_date' => '2026-08-15',
                'status' => 'activa',
            ])
            ->assertSessionHasErrors(['total_amount', 'installment_amount', 'installments_total']);
    }

    public function test_deduction_can_be_deleted(): void
    {
        $employee = Employee::factory()->create();
        $deduction = EmployeeDeduction::query()->create([
            'employee_id' => $employee->id,
            'type' => 'otra',
            'amount' => 300,
            'deduction_date' => '2026-08-15',
            'status' => 'activa',
        ]);

        $this->delete(route('employees.deductions.destroy', [$employee, $deduction]))
            ->assertRedirect(route('employees.show', $employee));

        $this->assertSoftDeleted('employee_deductions', ['id' => $deduction->id]);
    }

    public function test_cannot_delete_deduction_belonging_to_another_employee(): void
    {
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();
        $deduction = EmployeeDeduction::query()->create([
            'employee_id' => $otherEmployee->id,
            'type' => 'otra',
            'amount' => 300,
            'deduction_date' => '2026-08-15',
            'status' => 'activa',
        ]);

        $this->delete(route('employees.deductions.destroy', [$employee, $deduction]))
            ->assertNotFound();
    }

    public function test_attendance_delete_removes_justification_file(): void
    {
        Storage::fake('public');
        $employee = Employee::factory()->create();

        $this->post(route('employees.employee.attendances.store', $employee), [
            'employee_id' => $employee->id,
            'attendance_date' => '2026-08-15',
            'type' => EmployeeAttendance::TYPE_AUSENTE,
            'justification' => UploadedFile::fake()->create('justificacion.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $attendance = EmployeeAttendance::query()->firstOrFail();
        $this->assertNotNull($attendance->justification_path);
        Storage::disk('public')->assertExists($attendance->justification_path);

        $this->delete(route('employees.attendances.destroy', [$employee, $attendance]))
            ->assertRedirect(route('employees.show', $employee));

        Storage::disk('public')->assertMissing($attendance->justification_path);
    }

    public function test_cannot_delete_attendance_belonging_to_another_employee(): void
    {
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();
        $attendance = EmployeeAttendance::query()->create([
            'employee_id' => $otherEmployee->id,
            'attendance_date' => '2026-08-15',
            'type' => EmployeeAttendance::TYPE_AUSENTE,
        ]);

        $this->delete(route('employees.attendances.destroy', [$employee, $attendance]))
            ->assertNotFound();
    }

    public function test_document_can_be_downloaded_and_deleted(): void
    {
        Storage::fake('public');
        $employee = Employee::factory()->create();

        $this->post(route('employees.documents.store', $employee), [
            'name' => 'Cédula',
            'type' => 'identificacion',
            'file' => UploadedFile::fake()->create('cedula.pdf', 50, 'application/pdf'),
        ])->assertRedirect();

        $document = EmployeeDocument::query()->firstOrFail();
        Storage::disk('public')->assertExists($document->file_path);

        $this->get(route('employees.documents.download', [$employee, $document]))
            ->assertOk()
            ->assertDownload('cedula.pdf');

        $this->delete(route('employees.documents.destroy', [$employee, $document]))
            ->assertRedirect(route('employees.show', $employee));

        Storage::disk('public')->assertMissing($document->file_path);
        $this->assertSoftDeleted('employee_documents', ['id' => $document->id]);
    }

    public function test_cannot_download_document_belonging_to_another_employee(): void
    {
        Storage::fake('public');
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();

        $this->post(route('employees.documents.store', $otherEmployee), [
            'name' => 'Contrato',
            'type' => 'contrato',
            'file' => UploadedFile::fake()->create('contrato.pdf', 50, 'application/pdf'),
        ]);

        $document = EmployeeDocument::query()->firstOrFail();

        $this->get(route('employees.documents.download', [$employee, $document]))
            ->assertNotFound();
    }

    public function test_index_stats_reflect_employee_status_breakdown(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);
        Employee::factory()->for($role, 'role')->create(['status' => PersonnelService::STATUS_ACTIVO, 'inss_insured' => true]);
        Employee::factory()->for($role, 'role')->create(['status' => PersonnelService::STATUS_SUSPENDIDO, 'inss_insured' => false]);
        Employee::factory()->for($role, 'role')->create(['status' => PersonnelService::STATUS_RETIRADO, 'inss_insured' => true]);

        $this->get(route('employees.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.total', 3)
                ->where('stats.active', 1)
                ->where('stats.suspended', 1)
                ->where('stats.retired', 1)
                ->where('stats.inss_insured', 2));
    }
}
