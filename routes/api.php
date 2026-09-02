<?php

use App\Http\Controllers\Api\OfflineAuthController;
use App\Http\Controllers\Api\OfflineSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/offline/login', [OfflineAuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.offline.login');

Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
    Route::post('/offline/sync', OfflineSyncController::class)
        ->name('api.offline.sync');
});
