<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesAcopioFixtures;
use Tests\TestCase;

class DensityCompatibilityTest extends TestCase
{
    use CreatesAcopioFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAcopio();
    }

    public function test_producers_index_uses_native_app_density_in_weekly_report(): void
    {
        $this->createPrice(18.5);
        $route = $this->createRoute();
        $producer = $this->createProducer($route, ['full_name' => 'Productor con densidad']);
        $collection = $this->collectMilk($producer, 20, now()->toDateString(), $route, null);
        $collection->update(['density' => 26.125]);

        $this->get('/producers')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('report.rows.0.avg_density', 26.13)
                ->where('report.rows.0.densities.0', 26.125)
            );
    }
}
