<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProductController as WebProductController;
use App\Http\Controllers\Web\CustomerController as WebCustomerController;
use App\Http\Controllers\Web\OrderController as WebOrderController;
use App\Http\Controllers\Web\InventoryController as WebInventoryController;
use App\Http\Controllers\Web\ReportController as WebReportController;

// Public routes
Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Protected routes
Route::middleware('web.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Products Management
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [WebProductController::class, 'index'])->name('index');
        Route::get('/create', [WebProductController::class, 'create'])->name('create');
        Route::get('/{id}', [WebProductController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WebProductController::class, 'edit'])->name('edit');
    });
    
    // Customers Management
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [WebCustomerController::class, 'index'])->name('index');
        Route::get('/create', [WebCustomerController::class, 'create'])->name('create');
        Route::get('/{id}', [WebCustomerController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WebCustomerController::class, 'edit'])->name('edit');
    });
    
    // Orders Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [WebOrderController::class, 'index'])->name('index');
        Route::get('/create', [WebOrderController::class, 'create'])->name('create');
        Route::get('/{id}', [WebOrderController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [WebOrderController::class, 'edit'])->name('edit');
    });
    
    // Inventory Management
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [WebInventoryController::class, 'index'])->name('index');
        Route::get('/adjustments', [WebInventoryController::class, 'adjustments'])->name('adjustments');
        Route::get('/low-stock', [WebInventoryController::class, 'lowStock'])->name('low-stock');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [WebReportController::class, 'index'])->name('index');
        Route::get('/sales', [WebReportController::class, 'sales'])->name('sales');
        Route::get('/inventory', [WebReportController::class, 'inventory'])->name('inventory');
        Route::get('/customers', [WebReportController::class, 'customers'])->name('customers');
    });

    // Additional CRUD routes for Products
    Route::post('products', [WebProductController::class, 'store'])->name('products.store');
    Route::put('products/{id}', [WebProductController::class, 'update'])->name('products.update');
    Route::delete('products/{id}', [WebProductController::class, 'destroy'])->name('products.destroy');

    // Additional CRUD routes for Customers
    Route::post('customers', [WebCustomerController::class, 'store'])->name('customers.store');
    Route::put('customers/{id}', [WebCustomerController::class, 'update'])->name('customers.update');
    Route::delete('customers/{id}', [WebCustomerController::class, 'destroy'])->name('customers.destroy');

    // Additional CRUD routes for Orders
    Route::post('orders', [WebOrderController::class, 'store'])->name('orders.store');
    Route::put('orders/{id}', [WebOrderController::class, 'update'])->name('orders.update');
    Route::delete('orders/{id}', [WebOrderController::class, 'destroy'])->name('orders.destroy');
    Route::post('orders/{id}/payments', [WebOrderController::class, 'addPayment'])->name('orders.add-payment');

    // Inventory Adjustment routes
    Route::post('inventory/adjustment', [WebInventoryController::class, 'adjustment'])->name('inventory.adjustment');
    Route::post('inventory/bulk-adjustment', [WebInventoryController::class, 'bulkAdjustment'])->name('inventory.bulk-adjustment');

    // Report Export
    Route::post('reports/export', [WebReportController::class, 'export'])->name('reports.export');
    
    // POS/Cashier Interface
    Route::get('/pos', [WebOrderController::class, 'pos'])->name('pos');
});