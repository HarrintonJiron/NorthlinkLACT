<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeAbsence;
use App\Modules\Personnel\Models\EmployeeDeduction;
use App\Modules\Personnel\Models\EmployeeLoan;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Models\EmployeeVacation;
use App\Modules\Personnel\Models\TaxPolicy;
use App\Modules\Personnel\Requests\StorePayrollPeriodRequest;
use App\Modules\Personnel\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTaxPolicy(): TaxPolicy
    {
        return TaxPolicy::create([
            'name' => 'Tasas de prueba',
            'effective_from' => '2020-01-01',
            'inss_employee_rate' => 0.07,
            'inss_employer_rate' => 0.215,
            'inatec_rate' => 0.02,
            'ir_brackets' => [
                ['threshold' => 100000, 'rate' => 0],
                ['threshold' => 200000, 'rate' => 0.15],
                ['threshold' => 350000, 'rate' => 0.20],
                ['threshold' => 500000, 'rate' => 0.25],
                ['threshold' => null, 'rate' => 0.30],
            ],
        ]);
    }

    private function makeEmployee(array $overrides = []): Employee
    {
        $role = EmployeeRole::factory()->create();

        return Employee::factory()->for($role, 'role')->create(array_merge([
            'base_salary' => 30000,
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'hired_at' => '2020-01-01',
        ], $overrides));
    }

    public function test_full_calendar_month_pays_exactly_the_base_salary_regardless_of_month_length(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee();
        $service = app(PayrollService::class);

        $scenarios = [
            ['2028-03-01', '2028-03-31'], // 31 días
            ['2028-02-01', '2028-02-29'], // bisiesto, 29 días
            ['2029-02-01', '2029-02-28'], // no bisiesto, 28 días
            ['2028-04-01', '2028-04-30'], // 30 días
        ];

        foreach ($scenarios as [$start, $end]) {
            $period = $service->generatePeriod([
                'pay_frequency' => Employee::FREQ_MONTHLY,
                'period_start' => $start,
                'period_end' => $end,
            ]);
            $item = $period->items->firstWhere('employee_id', $employee->id);

            $this->assertEquals('30000.00', $item->gross_salary, "Falló para el periodo {$start} a {$end}");
        }
    }

    public function test_partial_monthly_period_still_prorates_over_its_real_days(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee();

        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-05-01',
            'period_end' => '2028-05-15',
        ]);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        $this->assertEquals('15000.00', $item->gross_salary);
    }

    public function test_unjustified_absence_inside_an_unpaid_vacation_is_not_deducted_twice(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee();

        EmployeeVacation::create([
            'employee_id' => $employee->id,
            'start_date' => '2028-02-10',
            'end_date' => '2028-02-20',
            'days' => 11,
            'paid' => false,
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        EmployeeAbsence::create(['employee_id' => $employee->id, 'date' => '2028-02-15', 'type' => 'unjustified']);

        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-02-01',
            'period_end' => '2028-02-29',
        ]);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        // 30 (mes comercial) - 11 (vacación sin goce, que ya cubre el 15) = 19 días pagados.
        $this->assertEquals('19000.00', $item->gross_salary);
    }

    public function test_deduction_larger_than_salary_floors_net_pay_at_zero(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee();
        EmployeeDeduction::create([
            'employee_id' => $employee->id,
            'concept' => 'Prueba',
            'amount' => 500000,
            'deduction_date' => '2028-04-05',
            'status' => 'pending',
        ]);

        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-04-01',
            'period_end' => '2028-04-30',
        ]);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        $this->assertEquals('0.00', $item->net_pay);
    }

    public function test_ir_is_annualized_with_the_employees_own_pay_frequency_not_the_periods(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee(['pay_frequency' => Employee::FREQ_WEEKLY, 'base_salary' => 25000]);
        $service = app(PayrollService::class);

        $period = $service->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-07-01',
            'period_end' => '2028-07-31',
        ]);
        $item = $period->items->firstWhere('employee_id', $employee->id);
        $policy = $period->taxPolicy;

        $expectedIr = $service->calculateIr((float) $item->gross_salary, (float) $item->inss_employee, Employee::FREQ_WEEKLY, $policy);

        $this->assertEquals($expectedIr, (float) $item->ir_amount);
    }

    public function test_generating_a_payroll_period_that_overlaps_an_existing_one_warns_and_names_the_affected_employees(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee();

        app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-09-01',
            'period_end' => '2028-09-30',
        ]);

        $request = StorePayrollPeriodRequest::create('/payroll', 'POST', [
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-09-15',
            'period_end' => '2028-09-25',
        ]);
        $request->setContainer($this->app);
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString($employee->full_name, $validator->errors()->first('period_overlap'));
    }

    public function test_confirm_duplicate_lets_an_overlapping_payroll_period_through(): void
    {
        $this->makeTaxPolicy();
        $this->makeEmployee();

        app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-09-01',
            'period_end' => '2028-09-30',
        ]);

        $request = StorePayrollPeriodRequest::create('/payroll', 'POST', [
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-09-15',
            'period_end' => '2028-09-25',
            'confirm_duplicate' => true,
        ]);
        $request->setContainer($this->app);
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_a_non_overlapping_payroll_period_needs_no_confirmation(): void
    {
        $this->makeTaxPolicy();
        $this->makeEmployee();

        $request = StorePayrollPeriodRequest::create('/payroll', 'POST', [
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-10-01',
            'period_end' => '2028-10-31',
        ]);
        $request->setContainer($this->app);
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    /**
     * Cada llamada a generatePeriod() es su propia transacción: la segunda
     * planilla debe descontar la cuota sobre el saldo YA actualizado por la
     * primera, no sobre un valor obsoleto — la misma garantía que protege dos
     * planillas generadas casi al mismo tiempo sobre el mismo préstamo.
     */
    public function test_loan_installments_across_separate_payroll_periods_use_the_latest_balance(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee();
        $loan = EmployeeLoan::create([
            'employee_id' => $employee->id,
            'amount' => 3000,
            'installment_amount' => 1000,
            'remaining_balance' => 3000,
            'status' => 'active',
            'granted_at' => '2028-01-01',
        ]);
        $service = app(PayrollService::class);

        $service->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-08-01',
            'period_end' => '2028-08-31',
        ]);
        $this->assertEquals('2000.00', $loan->fresh()->remaining_balance);

        $service->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-09-01',
            'period_end' => '2028-09-30',
        ]);
        $this->assertEquals('1000.00', $loan->fresh()->remaining_balance);
    }

    public function test_a_very_large_salary_computes_without_precision_loss(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee(['base_salary' => 9999999.99]);

        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-06-01',
            'period_end' => '2028-06-30',
        ]);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        $this->assertEquals('9999999.99', $item->gross_salary);
        $this->assertGreaterThan(0, (float) $item->net_pay);
        $this->assertLessThan((float) $item->gross_salary, (float) $item->net_pay);
    }

    public function test_a_salary_with_many_decimal_places_rounds_to_cents_consistently(): void
    {
        $this->makeTaxPolicy();
        $employee = $this->makeEmployee(['base_salary' => 12345.678]);

        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-06-01',
            'period_end' => '2028-06-30',
        ]);
        $item = $period->items->firstWhere('employee_id', $employee->id);

        // El bruto se guarda con 2 decimales exactos, sin arrastrar la fracción
        // del centavo de entrada indefinidamente.
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', (string) $item->gross_salary);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{2}$/', (string) $item->net_pay);
    }
}
