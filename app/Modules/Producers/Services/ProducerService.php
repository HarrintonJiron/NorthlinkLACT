<?php

namespace App\Modules\Producers\Services;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\MilkPrice;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Models\ProducerWeekAdjustment;
use App\Modules\Producers\Models\Route;
use Carbon\Carbon;

class ProducerService
{
    public function stats(?string $week = null): array
    {
        $total = Producer::query()->count();
        $active = Producer::query()->where('active', true)->count();
        $inactive = Producer::query()->where('active', false)->count();
        $weekEnd = $this->weekRange($week)['end'];
        $penalized = ProducerWeekAdjustment::query()
            ->whereDate('week_end', $weekEnd)
            ->whereNotNull('density_price')
            ->count();
        $newThisMonth = Producer::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $monthLabels = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $monthly = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $monthly->put($date->format('Y-m'), [
                'key' => $date->format('Y-m'),
                'label' => $monthLabels[(int) $date->format('n')],
                'count' => 0,
            ]);
        }

        Producer::query()
            ->where('created_at', '>=', now()->startOfMonth()->subMonths(11))
            ->get(['created_at'])
            ->groupBy(fn (Producer $producer) => $producer->created_at->format('Y-m'))
            ->each(function ($group, string $key) use ($monthly) {
                if ($monthly->has($key)) {
                    $month = $monthly->get($key);
                    $month['count'] = $group->count();
                    $monthly->put($key, $month);
                }
            });

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'penalized' => $penalized,
            'new_this_month' => $newThisMonth,
            'monthly' => $monthly->values()->all(),
        ];
    }

    public function routeLines(): array
    {
        $routes = Route::query()
            ->with(['activeAssignments.producer'])
            ->orderBy('name')
            ->get();

        $lines = $routes->map(function (Route $route) {
            $producers = $route->activeAssignments
                ->pluck('producer')
                ->filter()
                ->sortBy('full_name')
                ->values()
                ->map(fn (Producer $producer) => $this->producerPayload($producer));

            return [
                'id' => $route->id,
                'code' => $route->code,
                'name' => $route->name,
                'active' => $route->active,
                'producers' => $producers,
            ];
        })->all();

        $unassigned = Producer::query()
            ->whereDoesntHave('activeAssignment')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Producer $producer) => $this->producerPayload($producer))
            ->all();

        return [
            'routes' => $lines,
            'unassigned' => $unassigned,
        ];
    }

    public function currentPayThursday(?string $week = null): Carbon
    {
        if ($week) {
            $date = Carbon::parse($week)->startOfDay();

            return $date->isThursday() ? $date : $date->next(Carbon::THURSDAY);
        }

        $today = now()->startOfDay();
        $friday = $today->isFriday()
            ? $today->copy()
            : $today->copy()->previous(Carbon::FRIDAY);

        return $friday->copy()->addDays(6);
    }

    public function weekRange(?string $week = null): array
    {
        $end = $this->currentPayThursday($week);
        $start = $end->copy()->subDays(6);

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'pay_day' => $end->toDateString(),
            'label' => $start->format('d/m').' – '.$end->format('d/m/Y'),
        ];
    }

    public function weekOptions(): array
    {
        $current = $this->currentPayThursday();
        $options = [];

        for ($i = 0; $i < 8; $i++) {
            $end = $current->copy()->subWeeks($i);
            $start = $end->copy()->subDays(6);
            $options[] = [
                'value' => $end->toDateString(),
                'label' => $start->format('d/m').' – '.$end->format('d/m/Y'),
            ];
        }

        return $options;
    }

    public function nextCode(): string
    {
        $lastNumber = Producer::withTrashed()
            ->where('code', 'like', 'PRO-%')
            ->pluck('code')
            ->map(fn (string $code) => (int) preg_replace('/\D/', '', $code))
            ->max();

        return sprintf('PRO-%04d', ($lastNumber ?? 0) + 1);
    }

    public function weeklyPayroll(?int $routeId, ?string $week = null): array
    {
        $range = $this->weekRange($week);
        $price = (float) (MilkPrice::query()->effectiveOn($range['end'])->value('price_per_liter') ?? 0);

        $producers = Producer::query()
            ->with('activeAssignment.route')
            ->whereHas('activeAssignment', function ($query) use ($routeId) {
                if ($routeId) {
                    $query->where('route_id', $routeId);
                }
            })
            ->orderBy('full_name')
            ->get();

        $dayNames = [
            Carbon::FRIDAY => 'Vie',
            Carbon::SATURDAY => 'Sáb',
            Carbon::SUNDAY => 'Dom',
            Carbon::MONDAY => 'Lun',
            Carbon::TUESDAY => 'Mar',
            Carbon::WEDNESDAY => 'Mié',
            Carbon::THURSDAY => 'Jue',
        ];

        $days = [];
        $cursor = Carbon::parse($range['start'])->startOfDay();
        $weekEnd = Carbon::parse($range['end'])->startOfDay();
        while ($cursor->lte($weekEnd)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'label' => $dayNames[$cursor->dayOfWeek] ?? $cursor->format('D'),
                'day' => $cursor->format('d'),
            ];
            $cursor->addDay();
        }

        $collections = MilkCollection::query()
            ->whereDate('collection_date', '>=', $range['start'])
            ->whereDate('collection_date', '<=', $range['end'])
            ->when($routeId, fn ($query) => $query->where('route_id', $routeId))
            ->get(['producer_id', 'collection_date', 'liters', 'temperature'])
            ->groupBy('producer_id');

        $adjustments = ProducerWeekAdjustment::query()
            ->whereDate('week_end', $range['end'])
            ->whereIn('producer_id', $producers->pluck('id'))
            ->get()
            ->keyBy('producer_id');

        $rows = $producers->map(function (Producer $producer) use ($collections, $days, $price, $adjustments) {
            $daily = [];
            foreach ($days as $day) {
                $daily[$day['date']] = 0.0;
            }

            $densities = [];
            foreach ($collections->get($producer->id, []) as $collection) {
                $date = $collection->collection_date->toDateString();
                if (array_key_exists($date, $daily)) {
                    $daily[$date] = round($daily[$date] + (float) $collection->liters, 2);
                }
                if ($collection->temperature !== null) {
                    $densities[] = (float) $collection->temperature;
                }
            }

            $weekLiters = round(array_sum($daily), 2);
            $daysCollected = collect($daily)->filter(fn ($value) => $value > 0)->count();
            $adjustment = $adjustments->get($producer->id);
            $densityPrice = $adjustment?->density_price !== null
                ? (float) $adjustment->density_price
                : null;
            $appliedPrice = $densityPrice ?? $price;
            $advance = $adjustment ? (float) $adjustment->advance_amount : 0.0;
            $gross = round($weekLiters * $appliedPrice, 2);
            $amount = round($gross - $advance, 2);

            return [
                'id' => $producer->id,
                'code' => $producer->code,
                'full_name' => $producer->full_name,
                'identity_number' => $producer->identity_number,
                'phone' => $producer->phone,
                'address' => $producer->address,
                'community' => $producer->community,
                'municipality' => $producer->municipality,
                'department' => $producer->department,
                'active' => $producer->active,
                'route' => $producer->activeAssignment?->route
                    ? [
                        'id' => $producer->activeAssignment->route->id,
                        'code' => $producer->activeAssignment->route->code,
                        'name' => $producer->activeAssignment->route->name,
                    ]
                    : null,
                'daily' => $daily,
                'days' => $daysCollected,
                'liters' => $weekLiters,
                'base_price' => $price,
                'density_price' => $densityPrice,
                'price' => $appliedPrice,
                'advance_amount' => $advance,
                'gross_amount' => $gross,
                'amount' => $amount,
                'notes' => $adjustment?->notes,
                'avg_density' => count($densities)
                    ? round(array_sum($densities) / count($densities), 2)
                    : null,
                'densities' => $densities,
            ];
        })->values()->all();

        $dailyTotals = [];
        foreach ($days as $day) {
            $dailyTotals[$day['date']] = round(collect($rows)->sum(fn ($row) => $row['daily'][$day['date']] ?? 0), 2);
        }

        return [
            'week' => $range,
            'days' => $days,
            'price' => $price,
            'rows' => $rows,
            'totals' => [
                'producers' => count($rows),
                'days' => collect($rows)->sum('days'),
                'daily' => $dailyTotals,
                'liters' => round(collect($rows)->sum('liters'), 2),
                'gross_amount' => round(collect($rows)->sum('gross_amount'), 2),
                'advance_amount' => round(collect($rows)->sum('advance_amount'), 2),
                'amount' => round(collect($rows)->sum('amount'), 2),
                'penalized' => collect($rows)->whereNotNull('density_price')->count(),
            ],
        ];
    }

    protected function producerPayload(Producer $producer): array
    {
        return [
            'id' => $producer->id,
            'code' => $producer->code,
            'full_name' => $producer->full_name,
            'identity_number' => $producer->identity_number,
            'phone' => $producer->phone,
            'address' => $producer->address,
            'community' => $producer->community,
            'municipality' => $producer->municipality,
            'department' => $producer->department,
            'latitude' => $producer->latitude,
            'longitude' => $producer->longitude,
            'active' => $producer->active,
            'active_assignment' => $producer->relationLoaded('activeAssignment')
                ? $producer->activeAssignment
                : null,
        ];
    }
}
