<?php

namespace Tests\Feature;

use App\Modules\Finanzas\Models\FinanceTransaction;
use App\Modules\Inventory\Models\InventoryProduct;
use App\Modules\Inventory\Models\InventoryUnit;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    public function test_dashboard_displays_metrics_calculated_from_database_records(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 10:00:00'));
        $this->setUpAcopio();

        $northRoute = $this->createRoute(['name' => 'Ruta Norte']);
        $southRoute = $this->createRoute(['name' => 'Ruta Sur']);
        $maria = $this->createProducer($northRoute, ['full_name' => 'María']);
        $jose = $this->createProducer($northRoute, ['full_name' => 'José']);
        $ana = $this->createProducer($southRoute, ['full_name' => 'Ana']);

        $this->collectMilk($maria, 10, '2026-08-31');
        $todayCollection = $this->collectMilk($maria, 20, '2026-09-01', temperature: 4.2);
        $todayCollection->update([
            'acidity' => 6.8,
            'fat_percentage' => 3.5,
        ]);

        FinanceTransaction::query()->create([
            'code' => 'FIN-0001',
            'type' => FinanceTransaction::TYPE_INGRESO,
            'concept' => 'Venta semanal',
            'amount' => 1000,
            'transaction_date' => '2026-09-01',
            'active' => true,
        ]);
        FinanceTransaction::query()->create([
            'code' => 'FIN-0002',
            'type' => FinanceTransaction::TYPE_GASTO,
            'concept' => 'Combustible',
            'amount' => 250,
            'transaction_date' => '2026-09-01',
            'active' => true,
        ]);

        $unit = InventoryUnit::query()->create([
            'code' => 'UND',
            'name' => 'Unidad',
            'symbol' => 'und',
            'active' => true,
        ]);
        InventoryProduct::query()->create([
            'code' => 'PRD-0001',
            'name' => 'Filtro',
            'unit_id' => $unit->id,
            'stock' => 1,
            'min_stock' => 5,
            'active' => true,
        ]);
        InventoryProduct::query()->create([
            'code' => 'PRD-0002',
            'name' => 'Manguera',
            'unit_id' => $unit->id,
            'stock' => 10,
            'min_stock' => 2,
            'active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('userName', $this->user->name)
                ->where('overview.liters_today', 20)
                ->where('overview.liters_yesterday', 10)
                ->where('overview.liters_trend_percent', 100)
                ->where('overview.producers_attended', 1)
                ->where('overview.producers_scheduled', 3)
                ->where('overview.routes_completed', 0)
                ->where('overview.routes_in_progress', 1)
                ->where('overview.routes_pending', 1)
                ->where('overview.routes_total', 2)
                ->where('overview.finance.income', 1000)
                ->where('overview.finance.outflow', 250)
                ->where('overview.finance.movements', 2)
                ->where('overview.inventory.active', 2)
                ->where('overview.inventory.low_stock', 1)
                ->where('routesStatus.0.name', 'Ruta Norte')
                ->where('routesStatus.0.status', 'in_progress')
                ->where('routesStatus.0.liters', 20)
                ->where('routesStatus.0.progress', 50)
                ->where('routesStatus.1.name', 'Ruta Sur')
                ->where('routesStatus.1.status', 'pending')
                ->where('weeklyData.0.liters', 10)
                ->where('weeklyData.1.liters', 20)
                ->where('qualityMetrics.0.value', 14.6)
                ->where('qualityMetrics.1.value', 6.8)
                ->where('qualityMetrics.2.value', 3.5)
                ->where('qualityMetrics.3.value', 50)
                ->where('pendingOperations.0.type', 'Inventario bajo')
                ->has('recentActivity', 2)
                ->etc()
            );

        $this->travelBack();
    }

    public function test_dashboard_returns_zero_and_empty_states_when_there_are_no_operational_records(): void
    {
        $this->travelTo(Carbon::parse('2026-09-01 10:00:00'));
        $user = $this->authenticate();

        $this->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('userName', $user->name)
                ->where('overview.liters_today', 0)
                ->where('overview.producers_attended', 0)
                ->where('overview.routes_total', 0)
                ->where('overview.finance.income', 0)
                ->where('overview.inventory.active', 0)
                ->where('overview.alerts.total', 0)
                ->has('routesStatus', 0)
                ->has('weeklyData', 7)
                ->has('pendingOperations', 0)
                ->has('recentActivity', 0)
                ->etc()
            );

        $this->travelBack();
    }
}
