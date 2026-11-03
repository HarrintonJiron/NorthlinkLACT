<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Admin\Models\Company;
use App\Modules\Admin\Models\Plant;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MilkCollectionScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_and_opens_collections_from_assigned_company_and_plant(): void
    {
        $user = User::factory()->create(['active' => true, 'is_admin' => true]);
        [$ownCompany, $ownPlant, $ownCollection] = $this->collectionFixture('PROPIA');
        [, , $foreignCollection] = $this->collectionFixture('AJENA');
        $ownCompany->users()->attach($user->id, ['plant_id' => $ownPlant->id]);

        $this->actingAs($user)->get(route('collections.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('collections.data', 1)
                ->where('collections.data.0.id', $ownCollection->id));

        $this->actingAs($user)->get(route('collections.show', $ownCollection))->assertOk();
        $this->actingAs($user)->get(route('collections.show', $foreignCollection))->assertNotFound();
    }

    public function test_index_filters_scoped_collections_and_calculates_matching_summary(): void
    {
        $user = User::factory()->create(['active' => true, 'is_admin' => true]);
        [$company, $plant, $deliveredCollection] = $this->collectionFixture('FILTRO');
        $company->users()->attach($user->id, ['plant_id' => $plant->id]);

        $conflictCollection = MilkCollection::query()->create([
            'company_id' => $company->id,
            'plant_id' => $plant->id,
            'route_id' => $deliveredCollection->route_id,
            'producer_id' => $deliveredCollection->producer_id,
            'collection_date' => now()->subDay()->toDateString(),
            'liters' => 7.25,
            'visit_status' => 'rechazado',
            'sync_status' => 'conflict',
            'collected_by' => $user->id,
        ]);

        $this->actingAs($user)->get(route('collections.index', [
            'visit_status' => 'rechazado',
            'sync_status' => 'conflict',
            'date_from' => now()->subDays(2)->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('collections.data', 1)
                ->where('collections.data.0.id', $conflictCollection->id)
                ->where('summary.total_records', 1)
                ->where('summary.total_liters', '7.25')
                ->where('summary.synced_records', 0)
                ->where('summary.conflict_records', 1)
                ->has('routes', 1));
    }

    public function test_index_rejects_invalid_filter_ranges_and_values(): void
    {
        $user = User::factory()->create(['active' => true, 'is_admin' => true]);

        $this->actingAs($user)->get(route('collections.index', [
            'date_from' => '2026-11-03',
            'date_to' => '2026-11-01',
            'sync_status' => 'inventado',
        ]))
            ->assertRedirect()
            ->assertSessionHasErrors(['date_to', 'sync_status']);
    }

    private function collectionFixture(string $suffix): array
    {
        $company = Company::query()->create([
            'name' => "Empresa $suffix",
            'legal_name' => "Empresa $suffix",
            'tax_id' => "J-$suffix",
            'active' => true,
        ]);
        $plant = Plant::query()->create([
            'company_id' => $company->id,
            'name' => "Planta $suffix",
            'code' => "P-$suffix",
            'active' => true,
        ]);
        $route = Route::query()->create([
            'company_id' => $company->id,
            'plant_id' => $plant->id,
            'code' => "R-$suffix",
            'name' => "Ruta $suffix",
            'active' => true,
        ]);
        $producer = Producer::query()->create([
            'code' => "PRO-$suffix",
            'full_name' => "Productor $suffix",
            'active' => true,
        ]);
        $collection = MilkCollection::query()->create([
            'company_id' => $company->id,
            'plant_id' => $plant->id,
            'route_id' => $route->id,
            'producer_id' => $producer->id,
            'collection_date' => now()->toDateString(),
            'liters' => 10,
            'collected_by' => User::factory()->create()->id,
        ]);

        return [$company, $plant, $collection];
    }
}
