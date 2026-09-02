<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncRouteRunRequest;
use App\Modules\Offline\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;

class OfflineSyncController extends Controller
{
    public function __invoke(
        SyncRouteRunRequest $request,
        OfflineSyncService $offlineSyncService,
    ): JsonResponse {
        abort_unless($request->user()->tokenCan('offline:sync'), 403);

        $result = $offlineSyncService->sync(
            $request->user(),
            $request->validated(),
        );

        return response()->json(
            $result,
            $result['sync_status'] === 'conflict' ? 409 : 200,
        );
    }
}
