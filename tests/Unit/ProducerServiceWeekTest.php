<?php

namespace Tests\Unit;

use App\Modules\Producers\Services\ProducerService;
use Carbon\Carbon;
use Tests\TestCase;

class ProducerServiceWeekTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_current_week_runs_friday_through_thursday(): void
    {
        $service = new ProducerService;

        Carbon::setTestNow(Carbon::parse('2026-08-29'));
        $this->assertSame('2026-09-03', $service->currentPayThursday()->toDateString());
        $this->assertSame([
            'start' => '2026-08-28',
            'end' => '2026-09-03',
            'pay_day' => '2026-09-03',
            'label' => '28/08 – 03/09/2026',
        ], $service->weekRange());

        Carbon::setTestNow(Carbon::parse('2026-08-28'));
        $this->assertSame('2026-09-03', $service->currentPayThursday()->toDateString());

        Carbon::setTestNow(Carbon::parse('2026-09-03'));
        $this->assertSame('2026-09-03', $service->currentPayThursday()->toDateString());

        Carbon::setTestNow(Carbon::parse('2026-09-04'));
        $this->assertSame('2026-09-10', $service->currentPayThursday()->toDateString());
        $this->assertSame('2026-09-04', $service->weekRange()['start']);
    }

    public function test_week_filter_snaps_to_the_next_thursday(): void
    {
        $service = new ProducerService;

        $this->assertSame('2026-09-03', $service->currentPayThursday('2026-09-03')->toDateString());
        $this->assertSame('2026-09-03', $service->currentPayThursday('2026-08-28')->toDateString());
        $this->assertSame('2026-09-03', $service->currentPayThursday('2026-08-30')->toDateString());
    }
}
