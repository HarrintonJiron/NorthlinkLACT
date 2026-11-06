<?php

namespace App\Http\Middleware;

use App\Modules\Producers\Models\MilkCollection;
use App\Modules\Producers\Models\Producer;
use App\Modules\Producers\Services\RouteService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'companies' => $request->user()?->companies,
                'plants' => $request->user()?->plants,
                'modules' => $request->user()?->is_admin
                    ? ['*']
                    : $request->user()?->permissions()->pluck('module')->all(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'notifications' => fn () => $this->notifications($request),
        ];
    }

    /**
     * @return array<string, array{count: int, items: array<int, array<string, mixed>>}>
     */
    private function notifications(Request $request): array
    {
        $empty = [
            'field_producers' => ['count' => 0, 'items' => []],
            'correction_requests' => ['count' => 0, 'items' => []],
        ];
        $user = $request->user();

        if (! $user || (! $user->is_admin && ! $user->hasPermission('access_producers'))) {
            return $empty;
        }

        $accessibleRouteIds = app(RouteService::class)
            ->accessibleQuery($user)
            ->select('routes.id');

        $query = Producer::query()
            ->whereNotNull('external_uuid')
            ->where('code', 'like', 'CAMPO-%')
            ->whereNull('identity_number')
            ->whereHas('activeAssignment', fn ($assignment) => $assignment
                ->whereIn('route_id', $accessibleRouteIds));

        $items = (clone $query)
            ->with('activeAssignment.route:id,code,name')
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'code', 'full_name', 'phone', 'created_at'])
            ->map(fn (Producer $producer) => [
                'id' => $producer->id,
                'name' => $producer->full_name,
                'phone' => $producer->phone,
                'route' => $producer->activeAssignment?->route?->name,
                'created_at' => $producer->created_at?->toIso8601String(),
                'href' => route('producers.edit', $producer),
            ])
            ->all();

        $correctionQuery = MilkCollection::query()
            ->where('correction_status', MilkCollection::CORRECTION_REQUESTED)
            ->whereIn('route_id', $accessibleRouteIds);

        $correctionItems = (clone $correctionQuery)
            ->with([
                'producer:id,full_name',
                'route:id,name',
                'correctionRequestedBy:id,name',
            ])
            ->latest('correction_requested_at')
            ->limit(5)
            ->get(['id', 'producer_id', 'route_id', 'correction_reason', 'correction_requested_at', 'correction_requested_by'])
            ->map(fn (MilkCollection $collection) => [
                'id' => $collection->id,
                'producer' => $collection->producer?->full_name,
                'route' => $collection->route?->name,
                'operator' => $collection->correctionRequestedBy?->name,
                'reason' => $collection->correction_reason,
                'requested_at' => $collection->correction_requested_at?->toIso8601String(),
                'href' => route('collections.index', ['search' => $collection->producer?->full_name]),
            ])
            ->all();

        return [
            'field_producers' => [
                'count' => (clone $query)->count(),
                'items' => $items,
            ],
            'correction_requests' => [
                'count' => (clone $correctionQuery)->count(),
                'items' => $correctionItems,
            ],
        ];
    }
}
