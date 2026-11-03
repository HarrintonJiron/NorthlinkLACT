<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ModuleReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_module_progress_pdf(): void
    {
        $this->authenticate();

        $response = $this->get('/module-reports/inventory.pdf');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_official_report_can_be_exported_to_excel_compatible_workbook(): void
    {
        $this->authenticate();
        $response = $this->get('/module-reports/inventory.xls?from=2026-01-01&to=2026-12-31');
        $response->assertOk()->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('<Workbook', $response->getContent());
        $this->assertStringContainsString('Kardex oficial de inventario', $response->getContent());
    }

    public function test_guests_cannot_open_module_progress_pdf(): void
    {
        $this->get('/module-reports/inventory.pdf')
            ->assertRedirect(route('login'));
    }

    public function test_payroll_progress_pdf_is_not_exposed_by_this_demo_layer(): void
    {
        $this->authenticate();

        $this->get('/module-reports/payroll.pdf')
            ->assertNotFound();
    }

    public function test_report_center_only_lists_modules_available_to_user(): void
    {
        $reportsPermission = Permission::query()->create([
            'name' => 'access_reports',
            'display_name' => 'Reportes',
            'module' => 'reports',
        ]);
        $inventoryPermission = Permission::query()->create([
            'name' => 'access_inventory',
            'display_name' => 'Inventario',
            'module' => 'inventory',
        ]);
        $user = User::factory()->create(['active' => true, 'is_admin' => false]);
        $user->permissions()->attach([$reportsPermission->id, $inventoryPermission->id]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('reports', 2)
                ->where('reports.0.module', 'dashboard')
                ->where('reports.1.module', 'inventory')
                ->where('summary.available', 2));

        $this->get('/module-reports/inventory.pdf')->assertOk();
        $this->get('/module-reports/finances.pdf')->assertForbidden();
    }
}
