<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Producers\Controllers\RouteController;
use App\Modules\Producers\Controllers\MilkCollectionController;

Route::get('/', function () {
    return inertia('Admin/Dashboard');
})->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return inertia('Admin/Dashboard');
        })->name('dashboard');
    });

    Route::prefix('routes')->name('routes.')->group(function () {
        Route::get('/', [RouteController::class, 'index'])->name('index');
        Route::get('/create', [RouteController::class, 'create'])->name('create');
        Route::post('/', [RouteController::class, 'store'])->name('store');
        Route::get('/{route}', [RouteController::class, 'show'])->name('show');
    });

    Route::prefix('collections')->name('collections.')->group(function () {
        Route::get('/', [MilkCollectionController::class, 'index'])->name('index');
        Route::get('/create', [MilkCollectionController::class, 'create'])->name('create');
        Route::post('/', [MilkCollectionController::class, 'store'])->name('store');
        Route::get('/{collection}', [MilkCollectionController::class, 'show'])->name('show');
    });

    // Rutas en construcción - redirigen a página de "en construcción"
    Route::get('/producers', function () {
        return inertia('UnderConstruction');
    })->name('producers.index');

    Route::prefix('payment-sheets')->name('payment-sheets.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    // Ruta catch-all para cualquier página no implementada
    Route::get('/{any}', function () {
        return inertia('UnderConstruction');
    })->where('any', '.*');
});
