<?php

namespace Tests\Feature\Modules\Personnel;

use App\Modules\Personnel\Models\AguinaldoPeriod;
use App\Modules\Personnel\Models\Employee;
use App\Modules\Personnel\Models\EmployeeRole;
use App\Modules\Personnel\Models\EmployeeSettlement;
use App\Modules\Personnel\Models\PayrollPeriod;
use App\Modules\Personnel\Models\TaxPolicy;
use App\Modules\Personnel\Requests\StoreSettlementRequest;
use App\Modules\Personnel\Requests\StoreVacationRequest;
use App\Modules\Personnel\Services\AguinaldoService;
use App\Modules\Personnel\Services\EmployeeSettlementService;
use App\Modules\Personnel\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class HrGuardrailsTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $overrides = []): Employee
    {
        $role = EmployeeRole::factory()->create();

        return Employee::factory()->for($role, 'role')->create(array_merge([
            'base_salary' => 20000,
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'hired_at' => '2020-01-01',
        ], $overrides));
    }

    public function test_settlement_request_rejects_an_employee_without_a_base_salary(): void
    {
        $employee = $this->makeEmployee(['base_salary' => null]);

        $request = StoreSettlementRequest::create('/settlements', 'POST', [
            'employee_id' => $employee->id,
            'termination_type' => 'resignation',
            'termination_date' => '2028-01-15',
            'pending_salary_start' => '2028-01-01',
            'severance_method' => 'legal',
        ]);
        $request->setContainer($this->app);
        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('employee_id'));
    }

    public function test_vacation_request_is_rejected_when_it_exceeds_the_accrued_balance(): void
    {
        // Contratado hace 10 días: prácticamente 0 días acumulados.
        $employee = $this->makeEmployee(['hired_at' => now()->subDays(10)->toDateString()]);

        $request = StoreVacationRequest::create("/employees/{$employee->id}/vacations", 'POST', [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(95)->toDateString(),
            'paid' => true,
        ]);
        $request->setContainer($this->app);
        $request->setRouteResolver(fn () => new class($employee)
        {
            public function __construct(private Employee $employee) {}

            public function parameter($name)
            {
                return $name === 'employee' ? $this->employee : null;
            }
        });
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
    }

    public function test_unpaid_vacation_request_ignores_the_accrued_balance(): void
    {
        $employee = $this->makeEmployee(['hired_at' => now()->subDays(10)->toDateString()]);

        $request = StoreVacationRequest::create("/employees/{$employee->id}/vacations", 'POST', [
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(95)->toDateString(),
            'paid' => false,
        ]);
        $request->setContainer($this->app);
        $request->setRouteResolver(fn () => new class($employee)
        {
            public function __construct(private Employee $employee) {}

            public function parameter($name)
            {
                return $name === 'employee' ? $this->employee : null;
            }
        });
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_employee_vacation_balance_matches_the_360_day_commercial_year_convention(): void
    {
        $employee = $this->makeEmployee(['hired_at' => now()->subDays(360)->toDateString()]);

        $this->assertEquals(30.0, $employee->vacationBalance());
    }

    public function test_a_paid_payroll_period_cannot_be_re_approved(): void
    {
        $policy = TaxPolicy::create([
            'name' => 'Tasas de prueba', 'effective_from' => '2020-01-01',
            'inss_employee_rate' => 0.07, 'inss_employer_rate' => 0.215, 'inatec_rate' => 0.02,
            'ir_brackets' => [
                ['threshold' => 100000, 'rate' => 0],
                ['threshold' => null, 'rate' => 0.15],
            ],
        ]);
        $this->makeEmployee();
        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-10-01',
            'period_end' => '2028-10-31',
        ]);
        app(PayrollService::class)->approve($period);
        app(PayrollService::class)->markPaid($period, 'efectivo');

        $response = $this->patch(route('payroll.approve', $period));

        $response->assertStatus(422);
        $this->assertEquals(PayrollPeriod::STATUS_PAID, $period->fresh()->status);
    }

    /**
     * Simula dos peticiones de aprobación "al mismo tiempo": dos instancias del
     * mismo registro, cargadas por separado (como cargaría cada petición HTTP su
     * propio modelo), llamando approve() una tras otra. La actualización
     * atómica UPDATE...WHERE debe garantizar que solo la primera tenga efecto,
     * sin importar el orden de ejecución — es la misma garantía que protege
     * contra dos peticiones simultáneas reales.
     */
    public function test_two_near_simultaneous_approvals_of_the_same_payroll_period_only_one_succeeds(): void
    {
        TaxPolicy::create([
            'name' => 'Tasas de prueba', 'effective_from' => '2020-01-01',
            'inss_employee_rate' => 0.07, 'inss_employer_rate' => 0.215, 'inatec_rate' => 0.02,
            'ir_brackets' => [
                ['threshold' => 100000, 'rate' => 0],
                ['threshold' => null, 'rate' => 0.15],
            ],
        ]);
        $this->makeEmployee();
        $period = app(PayrollService::class)->generatePeriod([
            'pay_frequency' => Employee::FREQ_MONTHLY,
            'period_start' => '2028-11-01',
            'period_end' => '2028-11-30',
        ]);

        $requestA = PayrollPeriod::find($period->id);
        $requestB = PayrollPeriod::find($period->id);

        $resultA = app(PayrollService::class)->approve($requestA);
        $resultB = app(PayrollService::class)->approve($requestB);

        $this->assertTrue($resultA);
        $this->assertFalse($resultB);
        $this->assertEquals(PayrollPeriod::STATUS_APPROVED, $period->fresh()->status);
    }

    public function test_two_near_simultaneous_approvals_of_the_same_aguinaldo_only_one_succeeds(): void
    {
        $this->makeEmployee();
        $period = app(AguinaldoService::class)->generatePeriod(2028);

        $requestA = AguinaldoPeriod::find($period->id);
        $requestB = AguinaldoPeriod::find($period->id);

        $resultA = app(AguinaldoService::class)->approve($requestA);
        $resultB = app(AguinaldoService::class)->approve($requestB);

        $this->assertTrue($resultA);
        $this->assertFalse($resultB);
        $this->assertEquals(AguinaldoPeriod::STATUS_APPROVED, $period->fresh()->status);
    }

    public function test_two_near_simultaneous_settlement_payments_only_one_applies_its_side_effects(): void
    {
        $employee = $this->makeEmployee();
        $settlement = app(EmployeeSettlementService::class)->create($employee, [
            'termination_type' => 'resignation',
            'termination_date' => '2028-06-15',
            'pending_salary_start' => '2028-06-01',
            'severance_method' => 'legal',
        ]);
        app(EmployeeSettlementService::class)->approve($settlement);

        $requestA = EmployeeSettlement::find($settlement->id);
        $requestB = EmployeeSettlement::find($settlement->id);

        $resultA = app(EmployeeSettlementService::class)->markPaid($requestA, 'efectivo');
        $resultB = app(EmployeeSettlementService::class)->markPaid($requestB, 'transferencia');

        $this->assertTrue($resultA);
        $this->assertFalse($resultB);
        // El método de pago que quedó guardado es el de la primera petición que
        // realmente ganó la condición atómica, nunca el de la segunda.
        $this->assertEquals('efectivo', $settlement->fresh()->payment_method);
        $this->assertFalse($employee->fresh()->active);
    }
}
