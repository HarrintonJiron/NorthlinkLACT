<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\FinanceCategory;
use App\Modules\Finanzas\Models\FinanceTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinanzasModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_finanzas_index_seeds_categories_and_lists_transactions(): void
    {
        $this->get('/finanzas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Finanzas/Index')
                ->has('categories')
                ->has('typeOptions', 3)
                ->has('paymentMethods')
                ->where('stats.total', 0)
                ->where('transactions', [])
            );

        $this->assertDatabaseHas('finance_categories', ['code' => 'OPER', 'name' => 'Operaciones']);
        $this->assertGreaterThanOrEqual(5, FinanceCategory::query()->count());
    }

    public function test_store_transaction_with_auto_code_and_descriptions(): void
    {
        $this->get('/finanzas')->assertOk();
        $category = FinanceCategory::query()->where('code', 'SERV')->firstOrFail();

        $this->post('/finanzas/transactions', [
            'type' => FinanceTransaction::TYPE_GASTO,
            'category_id' => $category->id,
            'concept' => 'Combustible rutas',
            'description' => 'Diesel para camiones de acopio',
            'amount' => 12500.50,
            'transaction_date' => '2026-08-31',
            'payment_method' => 'transferencia',
            'reference' => 'FAC-2026-889',
            'payee' => 'Estación El Trébol',
            'notes' => 'Pago semanal combustible',
            'code' => 'HACK-999',
        ])
            ->assertRedirect('/finanzas')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('finance_transactions', [
            'concept' => 'Combustible rutas',
            'code' => 'FIN-0001',
            'type' => FinanceTransaction::TYPE_GASTO,
            'reference' => 'FAC-2026-889',
            'payee' => 'Estación El Trébol',
        ]);
        $this->assertDatabaseMissing('finance_transactions', [
            'code' => 'HACK-999',
        ]);
    }

    public function test_store_payment_and_income_types(): void
    {
        $this->get('/finanzas')->assertOk();
        $pagoCategory = FinanceCategory::query()->where('code', 'PROD')->firstOrFail();
        $ingresoCategory = FinanceCategory::query()->where('code', 'VENT')->firstOrFail();

        $this->post('/finanzas/transactions', [
            'type' => FinanceTransaction::TYPE_PAGO,
            'category_id' => $pagoCategory->id,
            'concept' => 'Pago productor semanal',
            'amount' => 45000,
            'transaction_date' => '2026-08-30',
            'payee' => 'José Martínez',
        ])->assertRedirect('/finanzas');

        $this->post('/finanzas/transactions', [
            'type' => FinanceTransaction::TYPE_INGRESO,
            'category_id' => $ingresoCategory->id,
            'concept' => 'Venta queso fresco',
            'amount' => 18000,
            'transaction_date' => '2026-08-31',
            'payee' => 'Distribuidora Norte',
        ])->assertRedirect('/finanzas');

        $this->get('/finanzas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('transactions', 2)
                ->where('stats.total', 2)
                ->where('stats.ingresos_mes', 18000)
                ->where('stats.pagos_mes', 45000)
            );
    }

    public function test_store_requires_concept_amount_and_date(): void
    {
        $this->get('/finanzas')->assertOk();

        $this->from('/finanzas')->post('/finanzas/transactions', [
            'type' => 'invalido',
            'concept' => '',
            'amount' => 0,
            'transaction_date' => '',
        ])
            ->assertRedirect('/finanzas')
            ->assertSessionHasErrors(['type', 'concept', 'amount', 'transaction_date']);
    }

    public function test_update_and_toggle_transaction(): void
    {
        $this->get('/finanzas')->assertOk();
        $category = FinanceCategory::query()->where('code', 'INS')->firstOrFail();

        $transaction = FinanceTransaction::query()->create([
            'code' => 'FIN-0005',
            'type' => FinanceTransaction::TYPE_GASTO,
            'category_id' => $category->id,
            'concept' => 'Insumos limpieza',
            'amount' => 500,
            'transaction_date' => '2026-08-20',
            'active' => true,
        ]);

        $this->put('/finanzas/transactions/'.$transaction->id, [
            'type' => FinanceTransaction::TYPE_GASTO,
            'category_id' => $category->id,
            'concept' => 'Insumos limpieza planta',
            'description' => 'Cloro y detergente industrial',
            'amount' => 850,
            'transaction_date' => '2026-08-21',
            'payment_method' => 'efectivo',
            'active' => true,
        ])
            ->assertRedirect('/finanzas')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('finance_transactions', [
            'id' => $transaction->id,
            'concept' => 'Insumos limpieza planta',
            'amount' => 850,
            'code' => 'FIN-0005',
        ]);

        $this->patch('/finanzas/transactions/'.$transaction->id.'/toggle')
            ->assertRedirect();

        $this->assertFalse($transaction->fresh()->active);
    }
}
