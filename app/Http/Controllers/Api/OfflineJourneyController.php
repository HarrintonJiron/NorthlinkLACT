<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OfflineJourneyRequest;
use App\Modules\Producers\Models\ProducerPaymentBatch;
use App\Modules\Producers\Models\ProducerPaymentItem;
use App\Modules\Producers\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class OfflineJourneyController extends Controller
{
    public function __invoke(OfflineJourneyRequest $request): JsonResponse
    {
        abort_unless($request->user()->tokenCan('offline:sync'), 403);

        $date = $request->date('date')->toDateString();
        $userId = $request->user()->id;

        $routes = Route::query()
            ->where('active', true)
            ->whereHas('operatorAssignments', fn ($assignment) => $assignment
                ->where('user_id', $userId)
                ->whereDate('assigned_date', $date))
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('company_user')
                ->whereColumn('company_user.company_id', 'routes.company_id')
                ->where('company_user.user_id', $userId)
                ->where(fn ($access) => $access
                    ->whereNull('company_user.plant_id')
                    ->orWhereColumn('company_user.plant_id', 'routes.plant_id')))
            ->with([
                'plant:id,name,code',
                'rutero:id,route_id,driver_name,vehicle_plate',
                'operatorAssignments' => fn ($query) => $query
                    ->where('user_id', $userId)
                    ->whereDate('assigned_date', $date),
                'assignments' => fn ($query) => $query
                    ->whereDate('assigned_at', '<=', $date)
                    ->where(fn ($assignment) => $assignment
                        ->whereNull('ended_at')
                        ->orWhereDate('ended_at', '>=', $date))
                    ->whereHas('producer', fn ($producer) => $producer->where('active', true))
                    ->with('producer:id,code,full_name,identity_number,phone,address,community,latitude,longitude')
                    ->orderBy('id'),
            ])
            ->orderBy('code')
            ->get(['id', 'company_id', 'plant_id', 'code', 'name']);

        $producerIds = $routes->flatMap(fn (Route $route) => $route->assignments->pluck('producer_id'))->unique();
        $paidThroughByProducer = ProducerPaymentItem::query()
            ->join('producer_payment_batches', 'producer_payment_batches.id', '=', 'producer_payment_items.batch_id')
            ->whereIn('producer_payment_items.producer_id', $producerIds)
            ->whereIn('producer_payment_batches.status', [
                ProducerPaymentBatch::STATUS_CLOSED,
                ProducerPaymentBatch::STATUS_PAID,
            ])
            ->groupBy('producer_payment_items.producer_id')
            ->selectRaw('producer_payment_items.producer_id, MAX(producer_payment_batches.week_end) AS paid_through')
            ->pluck('paid_through', 'producer_id')
            ->map(fn ($paidThrough): string => Carbon::parse($paidThrough)->toDateString());

        $manifestRoutes = $routes->map(function (Route $route) use ($paidThroughByProducer): array {
            $operatorAssignment = $route->operatorAssignments->first();

            return [
                'id' => $route->id,
                'code' => $route->code,
                'name' => $route->name,
                'plant' => $route->plant ? [
                    'id' => $route->plant->id,
                    'code' => $route->plant->code,
                    'name' => $route->plant->name,
                ] : null,
                'driver_name' => $route->rutero?->driver_name,
                'vehicle_plate' => $operatorAssignment?->vehicle_plate ?? $route->rutero?->vehicle_plate,
                'producers' => $route->assignments->values()->map(fn ($assignment, int $index): array => [
                    'id' => $assignment->producer->id,
                    'code' => $assignment->producer->code,
                    'full_name' => $assignment->producer->full_name,
                    'identity_number' => $assignment->producer->identity_number,
                    'phone' => $assignment->producer->phone,
                    'address' => $assignment->producer->address,
                    'community' => $assignment->producer->community,
                    'latitude' => $assignment->producer->latitude,
                    'longitude' => $assignment->producer->longitude,
                    'sequence' => $index + 1,
                    'paid_through' => $paidThroughByProducer->get($assignment->producer->id),
                ])->all(),
            ];
        })->values()->all();

        $manifestVersion = hash('sha256', json_encode([
            'date' => $date,
            'routes' => $manifestRoutes,
        ], JSON_THROW_ON_ERROR));

        return response()->json([
            'date' => $date,
            'manifest_version' => $manifestVersion,
            'downloaded_at' => now()->toIso8601String(),
            'routes' => $manifestRoutes,
        ]);
    }
}
