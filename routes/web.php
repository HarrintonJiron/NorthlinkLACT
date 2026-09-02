<?php

use App\Modules\Admin\Controllers\UserController;
use App\Modules\Finanzas\Controllers\FinanceTransactionController;
use App\Modules\Inventory\Controllers\InventoryProductController;
use App\Modules\Personnel\Controllers\AbsenceController;
use App\Modules\Personnel\Controllers\AguinaldoController;
use App\Modules\Personnel\Controllers\BonusController;
use App\Modules\Personnel\Controllers\DeductionController;
use App\Modules\Personnel\Controllers\EmployeeController;
use App\Modules\Personnel\Controllers\EmployeeHistoryController;
use App\Modules\Personnel\Controllers\EmployeeRoleController;
use App\Modules\Personnel\Controllers\LeaveController;
use App\Modules\Personnel\Controllers\LoanController;
use App\Modules\Personnel\Controllers\PayrollController;
use App\Modules\Personnel\Controllers\PayrollExportController;
use App\Modules\Personnel\Controllers\SettlementController;
use App\Modules\Personnel\Controllers\TaxPolicyController;
use App\Modules\Personnel\Controllers\VacationController;
use App\Modules\Producers\Controllers\MilkCollectionController;
use App\Modules\Producers\Controllers\ProducerController;
use App\Modules\Producers\Controllers\RouteController;
use App\Modules\Ruteros\Controllers\RuteroController;
use App\Modules\Sumni\Controllers\SumniController;
use Illuminate\Support\Facades\Route;

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
    Route::put('/{producer}/week-adjustment', [ProducerController::class, 'storeWeekAdjustment'])->name('week-adjustment');
    Route::get('/{producer}', [ProducerController::class, 'show'])->name('show');
    Route::get('/{producer}/edit', [ProducerController::class, 'edit'])->name('edit');
    Route::put('/{producer}', [ProducerController::class, 'update'])->name('update');
    Route::delete('/{producer}', [ProducerController::class, 'destroy'])->name('destroy');
});

// Rutas en construcción - redirigen a página de "en construcción"
Route::prefix('finanzas')->name('finanzas.')->group(function () {
    Route::get('/', [FinanceTransactionController::class, 'index'])->name('index');
    Route::post('/transactions', [FinanceTransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [FinanceTransactionController::class, 'update'])->name('transactions.update');
    Route::patch('/transactions/{transaction}/toggle', [FinanceTransactionController::class, 'toggle'])->name('transactions.toggle');
    Route::delete('/transactions/{transaction}', [FinanceTransactionController::class, 'destroy'])->name('transactions.destroy');
});

Route::prefix('payment-sheets')->name('payment-sheets.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('finanzas.index');
    })->name('index');
});

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('finanzas.index');
    })->name('index');
});

Route::prefix('production')->name('production.')->group(function () {
    Route::get('/', function () {
        return inertia('UnderConstruction');
    })->name('index');
});

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/', [InventoryProductController::class, 'index'])->name('index');
    Route::post('/products', [InventoryProductController::class, 'store'])->name('products.store');
    Route::post('/products/bulk', [InventoryProductController::class, 'storeBulk'])->name('products.bulk');
    Route::put('/products/{product}', [InventoryProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{product}/toggle', [InventoryProductController::class, 'toggle'])->name('products.toggle');
    Route::delete('/products/{product}', [InventoryProductController::class, 'destroy'])->name('products.destroy');
});

Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', [EmployeeController::class, 'index'])->name('index');
    Route::post('/', [EmployeeController::class, 'store'])->name('store');
    Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
    Route::patch('/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('status.update');
    Route::get('/{employee}', [EmployeeController::class, 'show'])->name('show');
    Route::get('/{employee}/history', [EmployeeHistoryController::class, 'show'])->name('history');
    Route::post('/roles', [EmployeeRoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{employeeRole}', [EmployeeRoleController::class, 'update'])->name('roles.update');
    Route::patch('/roles/{employeeRole}/status', [EmployeeRoleController::class, 'updateStatus'])->name('roles.status.update');

    Route::post('/{employee}/vacations', [VacationController::class, 'store'])->name('vacations.store');
    Route::put('/{employee}/vacations/{vacation}', [VacationController::class, 'update'])->name('vacations.update');
    Route::patch('/{employee}/vacations/{vacation}/status', [VacationController::class, 'updateStatus'])->name('vacations.status.update');
    Route::delete('/{employee}/vacations/{vacation}', [VacationController::class, 'destroy'])->name('vacations.destroy');

    Route::post('/{employee}/bonuses', [BonusController::class, 'store'])->name('bonuses.store');
    Route::put('/{employee}/bonuses/{bonus}', [BonusController::class, 'update'])->name('bonuses.update');
    Route::delete('/{employee}/bonuses/{bonus}', [BonusController::class, 'destroy'])->name('bonuses.destroy');

    Route::post('/{employee}/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::put('/{employee}/loans/{loan}', [LoanController::class, 'update'])->name('loans.update');
    Route::delete('/{employee}/loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

    Route::post('/{employee}/deductions', [DeductionController::class, 'store'])->name('deductions.store');
    Route::put('/{employee}/deductions/{deduction}', [DeductionController::class, 'update'])->name('deductions.update');
    Route::delete('/{employee}/deductions/{deduction}', [DeductionController::class, 'destroy'])->name('deductions.destroy');

    Route::post('/{employee}/leaves', [LeaveController::class, 'store'])->name('leaves.store');
    Route::put('/{employee}/leaves/{leave}', [LeaveController::class, 'update'])->name('leaves.update');
    Route::patch('/{employee}/leaves/{leave}/status', [LeaveController::class, 'updateStatus'])->name('leaves.status.update');
    Route::delete('/{employee}/leaves/{leave}', [LeaveController::class, 'destroy'])->name('leaves.destroy');

    Route::post('/{employee}/absences', [AbsenceController::class, 'store'])->name('absences.store');
    Route::delete('/{employee}/absences/{absence}', [AbsenceController::class, 'destroy'])->name('absences.destroy');
});

Route::prefix('payroll')->name('payroll.')->group(function () {
    Route::get('/', [PayrollController::class, 'index'])->name('index');
    Route::post('/', [PayrollController::class, 'store'])->name('store');
    Route::get('/{payrollPeriod}', [PayrollController::class, 'show'])->name('show');
    Route::put('/{payrollPeriod}/items/{item}', [PayrollController::class, 'updateItem'])->name('items.update');
    Route::patch('/{payrollPeriod}/approve', [PayrollController::class, 'approve'])->name('approve');
    Route::patch('/{payrollPeriod}/pay', [PayrollController::class, 'markPaid'])->name('pay');
    Route::delete('/{payrollPeriod}', [PayrollController::class, 'destroy'])->name('destroy');
    Route::get('/{payrollPeriod}/export', [PayrollExportController::class, 'exportPlanilla'])->name('print');
});

Route::post('/tax-policies', [TaxPolicyController::class, 'store'])->name('tax-policies.store');

Route::prefix('aguinaldo')->name('aguinaldo.')->group(function () {
    Route::post('/', [AguinaldoController::class, 'store'])->name('store');
    Route::get('/{aguinaldoPeriod}', [AguinaldoController::class, 'show'])->name('show');
    Route::patch('/{aguinaldoPeriod}/approve', [AguinaldoController::class, 'approve'])->name('approve');
    Route::patch('/{aguinaldoPeriod}/pay', [AguinaldoController::class, 'markPaid'])->name('pay');
    Route::delete('/{aguinaldoPeriod}', [AguinaldoController::class, 'destroy'])->name('destroy');
    Route::get('/{aguinaldoPeriod}/export', [PayrollExportController::class, 'exportAguinaldo'])->name('print');
});

Route::get('/payroll-export/{section}', [PayrollExportController::class, 'export'])->name('payroll.export');

Route::prefix('settlements')->name('settlements.')->group(function () {
    Route::post('/', [SettlementController::class, 'store'])->name('store');
    Route::get('/{settlement}', [SettlementController::class, 'show'])->name('show');
    Route::put('/{settlement}', [SettlementController::class, 'update'])->name('update');
    Route::patch('/{settlement}/approve', [SettlementController::class, 'approve'])->name('approve');
    Route::patch('/{settlement}/pay', [SettlementController::class, 'markPaid'])->name('pay');
    Route::delete('/{settlement}', [SettlementController::class, 'destroy'])->name('destroy');
    Route::get('/{settlement}/export', [PayrollExportController::class, 'exportSettlement'])->name('print');
});

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', function () {
        return inertia('UnderConstruction');
    })->name('index');
});

Route::prefix('settings')->name('settings.')->group(function () {
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
