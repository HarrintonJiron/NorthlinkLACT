<?php

namespace Tests\Feature;

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\ProducerRouteAssignment;
use App\Modules\Producers\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class RouteAdministrationTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_selected_producers_can_move_between_routes_without_rewriting_history(): void
    {
        $source = $this->createRoute(['name' => 'Ruta origen']);
        $target = $this->createRoute(['name' => 'Ruta destino']);
        $producer = $this->createProducer($source);
        $collection = $this->collectMilk($producer, 15, route: $source);

        $this->post(route('routes.producers.move', $source), [
            'target_route_id' => $target->id,
            'producer_ids' => [$producer->id],
            'reason' => 'Reorganización operativa de la ruta',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('producer_route_assignments', [
            'producer_id' => $producer->id,
            'route_id' => $target->id,
            'ended_at' => null,
        ]);
        $this->assertSame($source->id, $collection->fresh()->route_id);
        $this->assertDatabaseHas('audit_events', ['action' => 'producers_transferred']);
    }

    public function test_route_can_be_split_with_selected_producers(): void
    {
        $source = $this->createRoute(['name' => 'Ruta extensa']);
        $moving = $this->createProducer($source, ['full_name' => 'Productor a separar']);
        $staying = $this->createProducer($source, ['full_name' => 'Productor permanece']);

        $this->post(route('routes.split', $source), [
            'name' => 'Nueva ruta sector norte',
            'producer_ids' => [$moving->id],
            'reason' => 'Reducir tiempo y distancia del recorrido',
        ])->assertRedirect()->assertSessionHas('success');

        $newRoute = Route::query()->where('name', 'Nueva ruta sector norte')->firstOrFail();
        $this->assertSame($newRoute->id, $moving->fresh()->activeAssignment->route_id);
        $this->assertSame($source->id, $staying->fresh()->activeAssignment->route_id);
        $this->assertTrue($newRoute->active);
    }

    public function test_routes_can_merge_and_source_is_deactivated_with_assignments_preserved(): void
    {
        $source = $this->createRoute(['name' => 'Ruta a unir']);
        $target = $this->createRoute(['name' => 'Ruta consolidada']);
        $producer = $this->createProducer($source);
        $this->createRutero($source, ['owner_name' => 'Rutero transferido']);

        $this->post(route('routes.merge', $source), [
            'target_route_id' => $target->id,
            'reason' => 'Consolidación por baja cantidad de productores',
        ])->assertRedirect(route('routes.show', $target))->assertSessionHas('success');

        $this->assertFalse($source->fresh()->active);
        $this->assertSame($target->id, $producer->fresh()->activeAssignment->route_id);
        $this->assertSame('Rutero transferido', $target->fresh()->rutero->owner_name);
        $this->assertDatabaseHas('audit_events', [
            'entity_type' => Route::class,
            'entity_id' => $target->id,
            'action' => 'routes_merged',
        ]);
        $this->assertSame(1, ProducerRouteAssignment::query()->where('producer_id', $producer->id)->whereNull('ended_at')->count());
        $this->assertGreaterThanOrEqual(2, AuditEvent::query()->where('entity_type', Route::class)->count());
    }
}
