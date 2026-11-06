<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Services\ProducerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class OperationalReadinessTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_producer_transfer_details_are_saved_and_account_is_encrypted_at_rest(): void
    {
        $route = $this->createRoute();

        $this->post(route('producers.store'), [
            'full_name' => 'Productora Transferencia',
            'route_id' => $route->id,
            'payment_method' => 'transfer',
            'bank_name' => 'Banco de Prueba',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Productora Transferencia',
            'preferred_price_per_liter' => '22.5000',
            'active' => true,
        ])->assertRedirect(route('producers.index'));

        $producer = Producer::query()->where('full_name', 'Productora Transferencia')->firstOrFail();
        $this->assertSame('1234567890', $producer->bank_account_number);
        $this->assertSame('22.5000', $producer->preferred_price_per_liter);
        $this->assertNotSame('1234567890', $producer->getRawOriginal('bank_account_number'));
    }

    public function test_transfer_requires_complete_bank_details(): void
    {
        $route = $this->createRoute();

        $this->from(route('producers.create'))->post(route('producers.store'), [
            'full_name' => 'Productor sin cuenta',
            'route_id' => $route->id,
            'payment_method' => 'transfer',
            'active' => true,
        ])->assertRedirect(route('producers.create'))
            ->assertSessionHasErrors(['bank_name', 'bank_account_number', 'bank_account_holder']);

        $this->assertDatabaseMissing('producers', ['full_name' => 'Productor sin cuenta']);
    }

    public function test_preferred_producer_price_overrides_weekly_base_price(): void
    {
        $this->createPrice(18.5);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['preferred_price_per_liter' => 22.5]);
        $this->collectMilk($producer, 10);

        $row = collect(app(ProducerService::class)->weeklyPayroll($route->id)['rows'])->firstOrFail();

        $this->assertSame(22.5, $row['preferred_price']);
        $this->assertSame(22.5, $row['price']);
        $this->assertSame(225.0, $row['amount']);
    }

    public function test_requested_collection_correction_updates_record_and_preserves_audit_trail(): void
    {
        $this->company->users()->attach($this->user->id, ['plant_id' => $this->plant->id]);
        $route = $this->createRoute();
        $producer = $this->createProducer($route);
        $collection = $this->collectMilk($producer, 10);
        $collection->update([
            'correction_status' => MilkCollection::CORRECTION_REQUESTED,
            'correction_reason' => 'El rutero digitó mal los litros',
            'correction_requested_at' => now(),
            'correction_requested_by' => $this->user->id,
        ]);

        $this->patch(route('collections.correction.resolve', $collection), [
            'liters' => 12.25,
            'density' => 28.5,
            'notes' => 'Dato confirmado por llamada',
            'resolution_notes' => 'Se confirmó el valor correcto con el productor.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('milk_collections', [
            'id' => $collection->id,
            'liters' => 12.25,
            'correction_status' => MilkCollection::CORRECTION_RESOLVED,
            'correction_resolved_by' => $this->user->id,
        ]);
        $event = AuditEvent::query()->where('action', 'correction_resolved')->firstOrFail();
        $this->assertSame('10.00', $event->old_values['liters']);
        $this->assertSame('12.25', $event->new_values['liters']);
    }

    public function test_admin_can_deactivate_sunmi_device_and_revoke_its_session(): void
    {
        $device = Device::query()->create([
            'user_id' => $this->user->id,
            'device_uuid' => '10000000-0000-4000-8000-000000000777',
            'name' => 'SUNMI P3H 07',
            'platform' => 'android',
            'active' => true,
        ]);
        $this->user->createToken('device:'.$device->device_uuid);

        $this->patch(route('sumni.devices.toggle', $device))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($device->fresh()->active);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'device:'.$device->device_uuid,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'entity_type' => Device::class,
            'entity_id' => $device->id,
            'action' => 'device_deactivated',
        ]);
    }
}
