<?php

use App\Modules\Admin\Controllers\UserController;
use App\Modules\Auth\Controllers\AuthenticatedSessionController;
use App\Modules\Finanzas\Controllers\FinanceTransactionController;
use App\Modules\Inventory\Controllers\InventoryProductController;
use App\Modules\Personnel\Controllers\EmployeeAttendanceController;
use App\Modules\Personnel\Controllers\EmployeeController;
use App\Modules\Personnel\Controllers\EmployeeDeductionController;
use App\Modules\Personnel\Controllers\EmployeeDocumentController;
use App\Modules\Personnel\Controllers\EmployeeRoleController;
use App\Modules\Producers\Controllers\MilkCollectionController;
use App\Modules\Producers\Controllers\ProducerController;
use App\Modules\Producers\Controllers\RouteController;
use App\Modules\Ruteros\Controllers\RuteroController;
use App\Modules\Sumni\Controllers\SumniController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', function () {
        return inertia('Admin/Dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', function () {
            return inertia('Admin/Dashboard');
        })->name('dashboard');
    });

    Route::prefix('sumni')->name('sumni.')->middleware('module.permission:sumni')->group(function () {
        Route::get('/', [SumniController::class, 'index'])->name('index');
        Route::get('/{route}', [SumniController::class, 'show'])->name('show');
        Route::post('/{route}/producers', [SumniController::class, 'storeProducer'])->name('producers.store');
        Route::post('/{route}', [SumniController::class, 'store'])->name('store');
    });

    Route::prefix('ruteros')->name('ruteros.')->middleware('module.permission:ruteros')->group(function () {
        Route::get('/', [RuteroController::class, 'index'])->name('index');
        Route::post('/', [RuteroController::class, 'store'])->name('store');
        Route::patch('/{rutero}/toggle', [RuteroController::class, 'toggle'])->name('toggle');
        Route::put('/{rutero}', [RuteroController::class, 'update'])->name('update');
        Route::get('/{rutero}', [RuteroController::class, 'show'])->name('show');
    });

    Route::prefix('routes')->name('routes.')->middleware('module.permission:routes')->group(function () {
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

    Route::prefix('collections')->name('collections.')->middleware('module.permission:collections')->group(function () {
        Route::get('/', [MilkCollectionController::class, 'index'])->name('index');
        Route::get('/create', [MilkCollectionController::class, 'create'])->name('create');
        Route::post('/', [MilkCollectionController::class, 'store'])->name('store');
        Route::get('/{collection}', [MilkCollectionController::class, 'show'])->name('show');
    });

    Route::prefix('producers')->name('producers.')->middleware('module.permission:producers')->group(function () {
        Route::get('/', [ProducerController::class, 'index'])->name('index');
        Route::get('/create', [ProducerController::class, 'create'])->name('create');
        Route::post('/', [ProducerController::class, 'store'])->name('store');
        Route::put('/{producer}/week-adjustment', [ProducerController::class, 'storeWeekAdjustment'])->name('week-adjustment');
        Route::get('/{producer}', [ProducerController::class, 'show'])->name('show');
        Route::get('/{producer}/edit', [ProducerController::class, 'edit'])->name('edit');
        Route::put('/{producer}', [ProducerController::class, 'update'])->name('update');
        Route::delete('/{producer}', [ProducerController::class, 'destroy'])->name('destroy');
    });

    // Rutas en construcción - redirigen a página de "en construcción"
    Route::prefix('finanzas')->name('finanzas.')->middleware('module.permission:finances')->group(function () {
        Route::get('/', [FinanceTransactionController::class, 'index'])->name('index');
        Route::post('/transactions', [FinanceTransactionController::class, 'store'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [FinanceTransactionController::class, 'update'])->name('transactions.update');
        Route::patch('/transactions/{transaction}/toggle', [FinanceTransactionController::class, 'toggle'])->name('transactions.toggle');
        Route::delete('/transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])->name('transactions.destroy');
    });

    Route::prefix('payment-sheets')->name('payment-sheets.')->middleware('module.permission:finances')->group(function () {
        Route::get('/', function () {
            return redirect()->route('finanzas.index');
        })->name('index');
    });

    Route::prefix('payments')->name('payments.')->middleware('module.permission:finances')->group(function () {
        Route::get('/', function () {
            return redirect()->route('finanzas.index');
        })->name('index');
    });

    Route::prefix('production')->name('production.')->middleware('module.permission:production')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('inventory')->name('inventory.')->middleware('module.permission:inventory')->group(function () {
        Route::get('/', [InventoryProductController::class, 'index'])->name('index');
        Route::post('/products', [InventoryProductController::class, 'store'])->name('products.store');
        Route::post('/products/bulk', [InventoryProductController::class, 'storeBulk'])->name('products.bulk');
        Route::put('/products/{product}', [InventoryProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/toggle', [InventoryProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}', [InventoryProductController::class, 'destroy'])->name('products.destroy');
    });

    Route::prefix('employees')->name('employees.')->middleware('module.permission:personnel')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::patch('/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('status.update');
        Route::post('/attendances', [EmployeeAttendanceController::class, 'store'])->name('attendances.store');
        Route::post('/{employee}/attendances', [EmployeeAttendanceController::class, 'store'])->name('employee.attendances.store');
        Route::delete('/{employee}/attendances/{attendance}', [EmployeeAttendanceController::class, 'destroy'])->name('attendances.destroy');
        Route::post('/deductions', [EmployeeDeductionController::class, 'store'])->name('deductions.store');
        Route::post('/{employee}/deductions', [EmployeeDeductionController::class, 'store'])->name('employee.deductions.store');
        Route::delete('/{employee}/deductions/{deduction}', [EmployeeDeductionController::class, 'destroy'])->name('deductions.destroy');
        Route::post('/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('documents.store');
        Route::get('/{employee}/documents/{document}/download', [EmployeeDocumentController::class, 'download'])->name('documents.download');
        Route::delete('/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/roles', [EmployeeRoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{employeeRole}', [EmployeeRoleController::class, 'update'])->name('roles.update');
        Route::patch('/roles/{employeeRole}/status', [EmployeeRoleController::class, 'updateStatus'])->name('roles.status.update');
    });

    Route::prefix('payroll')->name('payroll.')->middleware('module.permission:payroll')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('reports')->name('reports.')->middleware('module.permission:reports')->group(function () {
        Route::get('/', function () {
            return inertia('UnderConstruction');
        })->name('index');
    });

    Route::prefix('settings')->name('settings.')->middleware('administrator')->group(function () {
        Route::get('/', function () {
            return inertia('Settings/Index');
        })->name('index');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
    });

    // Ruta catch-all para cualquier página no implementada
    Route::get('/{any}', function () {
        return inertia('UnderConstruction');
    })->where('any', '.*');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
