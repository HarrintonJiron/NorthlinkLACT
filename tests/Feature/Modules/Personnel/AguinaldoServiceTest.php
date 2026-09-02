<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Services\AguinaldoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AguinaldoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $overrides = []): Employee
    {
        $role = EmployeeRole::factory()->create();

        return Employee::factory()->for($role, 'role')->create(array_merge([
            'base_salary' => 12000,
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'hired_at' => '2020-01-01',
        ], $overrides));
    }

    public function test_a_full_period_of_employment_pays_exactly_one_month_of_salary(): void
    {
        // Aguinaldo 2025: periodo 01/12/2024 - 30/11/2025, ya transcurrido por completo.
        $employee = $this->makeEmployee();

        $period = app(AguinaldoService::class)->generatePeriod(2025);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        $this->assertEquals(360, $item->days_employed);
        $this->assertEquals('12000.00', $item->amount);
    }

    public function test_employment_starting_mid_period_prorates_the_aguinaldo(): void
    {
        $employee = $this->makeEmployee(['hired_at' => '2025-09-01']);

        $period = app(AguinaldoService::class)->generatePeriod(2025);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        $this->assertLessThan(360, $item->days_employed);
        $this->assertEquals(round(12000 * $item->days_employed / 360, 2), (float) $item->amount);
    }

    public function test_employee_hired_the_day_the_period_starts_gets_the_full_360_days(): void
    {
        $employee = $this->makeEmployee(['hired_at' => '2024-12-01']);

        $period = app(AguinaldoService::class)->generatePeriod(2025);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        $this->assertEquals(360, $item->days_employed);
    }

    public function test_employee_hired_the_day_after_the_period_ends_is_not_included(): void
    {
        $employee = $this->makeEmployee(['hired_at' => '2025-12-01']);

        $period = app(AguinaldoService::class)->generatePeriod(2025);

        $this->assertNull($period->items->firstWhere('employee_id', $employee->id));
    }

    public function test_generating_aguinaldo_for_a_year_that_already_exists_is_rejected(): void
    {
        $this->makeEmployee();
        $service = app(AguinaldoService::class);
        $service->generatePeriod(2025);

        $this->expectException(RuntimeException::class);
        $service->generatePeriod(2025);
    }
}
