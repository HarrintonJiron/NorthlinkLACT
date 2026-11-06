<?php

namespace Tests\Feature;

use App\Modules\Producers\Models\MilkCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class FieldProducerNotificationTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_quick_field_producer_is_shared_as_an_admin_notification(): void
    {
        $route = $this->createRoute(['name' => 'Ruta SUNMI']);
        $producer = $this->createProducer($route, [
            'code' => 'CAMPO-ABC123',
            'external_uuid' => 'f68f9630-d056-4070-87c6-59b5d0ee5f15',
            'full_name' => 'Cliente rápido',
            'identity_number' => null,
            'phone' => '8888-1111',
        ]);

        $this->get('/producers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.field_producers.count', 1)
                ->where('notifications.field_producers.items.0.id', $producer->id)
                ->where('notifications.field_producers.items.0.name', 'Cliente rápido')
                ->where('notifications.field_producers.items.0.route', 'Ruta SUNMI')
                ->where('notifications.field_producers.items.0.href', route('producers.edit', $producer)));
    }

    public function test_notification_disappears_after_official_identity_is_completed(): void
    {
        $route = $this->createRoute();
        $producer = $this->createProducer($route, [
            'code' => 'CAMPO-DEF456',
            'external_uuid' => '213b40f4-d7d0-4f1d-bf06-0ae1ee95b7f7',
            'identity_number' => null,
        ]);

        $producer->update(['identity_number' => '441-010101-0001A']);

        $this->get('/producers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.field_producers.count', 0)
                ->has('notifications.field_producers.items', 0));
    }

    public function test_rutero_correction_request_is_shared_as_an_admin_notification(): void
    {
        $route = $this->createRoute(['name' => 'Ruta Bethel Kubaly']);
        $producer = $this->createProducer($route, ['full_name' => 'Productor con incidencia']);
        $collection = MilkCollection::factory()->create([
            'company_id' => $this->company->id,
            'plant_id' => $this->plant->id,
            'route_id' => $route->id,
            'producer_id' => $producer->id,
            'collected_by' => $this->user->id,
            'correction_status' => MilkCollection::CORRECTION_REQUESTED,
            'correction_reason' => 'Revisar litros digitados.',
            'correction_requested_at' => now(),
            'correction_requested_by' => $this->user->id,
        ]);

        $this->get('/collections')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('notifications.correction_requests.count', 1)
                ->where('notifications.correction_requests.items.0.id', $collection->id)
                ->where('notifications.correction_requests.items.0.producer', 'Productor con incidencia')
                ->where('notifications.correction_requests.items.0.route', 'Ruta Bethel Kubaly')
                ->where('notifications.correction_requests.items.0.reason', 'Revisar litros digitados.'));
    }
}
