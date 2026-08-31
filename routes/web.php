<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Producers\Controllers\RouteController;
use App\Modules\Producers\Controllers\MilkCollectionController;
use App\Modules\Producers\Controllers\ProducerController;
use App\Modules\Sumni\Controllers\SumniController;
use App\Modules\Ruteros\Controllers\RuteroController;

Route::get('/', function () {
    return inertia('Admin/Dashboard');
})->name('dashboard');

// Temporalmente sin middleware auth para desarrollo
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return inertia('Admin/Dashboard');
    })->name('dashboard');
});

Route::prefix('sumni')->name('sumni.')->group(function () {
    Route::get('/', [SumniController::class, 'index'])->name('index');
    Route::get('/{route}', [SumniController::class, 'show'])->name('show');
    Route::post('/{route}/producers', [SumniController::class, 'storeProducer'])->name('producers.store');
    Route::post('/{route}', [SumniController::class, 'store'])->name('store');
});

Route::prefix('ruteros')->name('ruteros.')->group(function () {
    Route::get('/', [RuteroController::class, 'index'])->name('index');
    Route::post('/', [RuteroController::class, 'store'])->name('store');
    Route::patch('/{rutero}/toggle', [RuteroController::class, 'toggle'])->name('toggle');
    Route::put('/{rutero}', [RuteroController::class, 'update'])->name('update');
    Route::get('/{rutero}', [RuteroController::class, 'show'])->name('show');
});

Route::prefix('routes')->name('routes.')->group(function () {
    Route::get('/', [RouteController::class, 'index'])->name('index');
    Route::get('/create', [RouteController::class, 'create'])->name('create');
    Route::post('/', [RouteController::class, 'store'])->name('store');
    Route::patch('/{route}/toggle', [RouteController::class, 'toggle'])->name('toggle');
    Route::post('/{route}/collections', [RouteController::class, 'storeCollection'])->name('collections.store');
    Route::post('/{route}/assign-rutero', [RouteController::class, 'assignRutero'])->name('rutero.assign');
    Route::delete('/{route}/rutero', [RouteController::class, 'unassignRutero'])->name('rutero.unassign');
    Route::put('/{route}', [RouteController::class, 'update'])->name('update');
    Route::get('/{route}', [RouteController::class, 'show'])->name('show');
});

Route::prefix('collections')->name('collections.')->group(function () {
    Route::get('/', [MilkCollectionController::class, 'index'])->name('index');
    Route::get('/create', [MilkCollectionController::class, 'create'])->name('create');
    Route::post('/', [MilkCollectionController::class, 'store'])->name('store');
    Route::get('/{collection}', [MilkCollectionController::class, 'show'])->name('show');
});

Route::prefix('producers')->name('producers.')->group(function () {
    Route::get('/', [ProducerController::class, 'index'])->name('index');
    Route::get('/create', [ProducerController::class, 'create'])->name('create');
    Route::post('/', [ProducerController::class, 'store'])->name('store');
    Route::get('/{producer}', [ProducerController::class, 'show'])->name('show');
    Route::get('/{producer}/edit', [ProducerController::class, 'edit'])->name('edit');
    Route::put('/{producer}', [ProducerController::class, 'update'])->name('update');
    Route::delete('/{producer}', [ProducerController::class, 'destroy'])->name('destroy');
});

// Rutas en construcción - redirigen a página de "en construcción"
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
