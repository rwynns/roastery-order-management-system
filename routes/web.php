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
    
    // POS/Cashier Interface
    Route::get('/pos', [WebOrderController::class, 'pos'])->name('pos');
});