<?php

namespace Tests\Unit\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\PersonnelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelServiceTest extends TestCase
{
    use RefreshDatabase;

    private PersonnelService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PersonnelService::class);
    }

    public function test_next_employee_code_increments_sequentially(): void
    {
        $role = EmployeeRole::factory()->create();

        Employee::factory()->for($role, 'role')->create(['code' => 'EMP-0007']);

        $this->assertSame('EMP-0008', $this->service->nextEmployeeCode());
    }

    public function test_inss_deduction_is_null_when_employee_is_not_insured(): void
    {
        $employee = Employee::factory()->create([
            'inss_insured' => false,
            'salary' => 20000,
        ]);

        $this->assertNull($this->service->inssDeductionForEmployee($employee));
    }

    public function test_inss_deduction_is_null_when_salary_is_missing_or_zero(): void
    {
        $withoutSalary = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => null,
        ]);

        $zeroSalary = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => 0,
        ]);

        $this->assertNull($this->service->inssDeductionForEmployee($withoutSalary));
        $this->assertNull($this->service->inssDeductionForEmployee($zeroSalary));
    }

    public function test_inss_deduction_calculates_seven_percent_of_gross_salary(): void
    {
        $employee = Employee::factory()->create([
            'inss_insured' => true,
            'salary' => 15555.55,
        ]);

        $deduction = $this->service->inssDeductionForEmployee($employee);

        $this->assertNotNull($deduction);
        $this->assertSame(1088.89, $deduction['amount']);
        $this->assertSame('7%', $deduction['rate_label']);
        $this->assertTrue($deduction['automatic']);
    }

    public function test_deduction_type_options_exclude_manual_inss(): void
    {
        $values = array_column($this->service->deductionTypeOptions(), 'value');

        $this->assertNotContains(PersonnelService::DEDUCTION_INSS, $values);
        $this->assertContains(PersonnelService::DEDUCTION_ADELANTO_SALARIO, $values);
    }

    public function test_stats_include_status_and_inss_counters(): void
    {
        $role = EmployeeRole::factory()->create(['active' => true]);

        Employee::factory()->for($role, 'role')->create(['status' => 'activo', 'inss_insured' => true]);
        Employee::factory()->for($role, 'role')->create(['status' => 'suspendido', 'inss_insured' => false]);
        Employee::factory()->for($role, 'role')->create(['status' => 'retirado', 'inss_insured' => true]);

        $stats = $this->service->stats();

        $this->assertSame(3, $stats['total']);
        $this->assertSame(1, $stats['active']);
        $this->assertSame(1, $stats['suspended']);
        $this->assertSame(1, $stats['retired']);
        $this->assertSame(2, $stats['inss_insured']);
        $this->assertSame(1, $stats['roles']);
        $this->assertCount(12, $stats['monthly']);
    }
}
