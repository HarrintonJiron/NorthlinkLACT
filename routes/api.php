<?php

use App\Http\Controllers\Api\OfflineAuthController;
use App\Http\Controllers\Api\OfflineJourneyController;
use App\Http\Controllers\Api\OfflineSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/offline/login', [OfflineAuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.offline.login');

Route::middleware(['auth:sanctum', 'user.active', 'offline.device'])->group(function () {
    Route::get('/offline/journey', OfflineJourneyController::class)
        ->name('api.offline.journey');

    Route::post('/offline/sync', OfflineSyncController::class)
        ->name('api.offline.sync');
});
