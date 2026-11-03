<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_update_company_and_manage_plants(): void
    {
        $this->authenticate();
        $company = Company::factory()->create();

        $this->get(route('settings.organization.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Organization')
                ->where('company.id', $company->id));

        $this->put(route('settings.organization.update', $company), [
            'name' => 'Lácteos Quebar',
            'legal_name' => 'Lácteos Quebar S.A.',
            'tax_id' => 'J0310000000001',
            'address' => 'Waslala, Nicaragua',
            'phone' => '8888-0000',
            'email' => 'administracion@quebar.test',
            'currency' => 'NIO',
        ])->assertRedirect()->assertSessionHas('success');

        $this->post(route('settings.plants.store'), [
            'name' => 'Planta de quesos',
            'code' => 'PLT-QUESO',
            'address' => 'Zona rural',
        ])->assertRedirect()->assertSessionHas('success');

        $plant = Plant::query()->firstOrFail();
        $this->patch(route('settings.plants.status', $plant))->assertRedirect();
        $this->assertFalse($plant->fresh()->active);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Lácteos Quebar']);
        $this->assertDatabaseCount('audit_events', 3);
    }

    public function test_non_administrator_cannot_access_organization_settings(): void
    {
        Company::factory()->create();
        $this->actingAs(User::factory()->create(['active' => true, 'is_admin' => false]));

        $this->get(route('settings.organization.index'))->assertForbidden();
        $this->post(route('settings.plants.store'), [
            'name' => 'Sede no autorizada',
            'code' => 'NO-AUTH',
        ])->assertForbidden();
    }
}
