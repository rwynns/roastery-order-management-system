<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\InventoryController;

// public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Resource routes
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('orders', OrderController::class);

    // Custom order routes
    Route::post('orders/{order}/payments', [OrderController::class, 'addPayment']);
    Route::get('orders-analytics', [OrderController::class, 'analytics']);

    // Custom customer routes
    Route::get('customers-analytics', [CustomerController::class, 'analytics']);
    Route::get('customers/{customer}/order-history', [CustomerController::class, 'orderHistory']);
    Route::post('customers/bulk-status', [CustomerController::class, 'bulkUpdateStatus']);
    Route::get('customers-export', [CustomerController::class, 'export']);

    // Inventory routes
    Route::apiResource('inventories', InventoryController::class);
    
    // Custom inventory routes
    Route::get('inventories-analytics', [InventoryController::class, 'analytics']);
    Route::get('inventories-low-stock', [InventoryController::class, 'lowStock']);
    Route::get('inventories-out-of-stock', [InventoryController::class, 'outOfStock']);
    Route::post('inventories-stock-adjustment', [InventoryController::class, 'stockAdjustment']);
    Route::post('inventories-bulk-adjustment', [InventoryController::class, 'bulkStockAdjustment']);
    Route::get('inventories-export', [InventoryController::class, 'export']);

        // Report routes
    Route::prefix('reports')->group(function () {
        Route::get('sales', [ReportController::class, 'salesReport']);
        Route::get('inventory', [ReportController::class, 'inventoryReport']);
        Route::get('customers', [ReportController::class, 'customerReport']);
        Route::get('financial', [ReportController::class, 'financialReport']);
        Route::get('product-performance', [ReportController::class, 'productPerformanceReport']);
        Route::post('export', [ReportController::class, 'exportReport']);
    });


    // Custom routes
    Route::post('orders/{order}/payments', [OrderController::class, 'addPayment']);
    Route::get('reports/sales', [ReportController::class, 'salesReport']);
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);

    // Addtional product routes
    Route::get('products/low-stock',[ProductController::class, 'lowStock']);
    Route::post('products/bulk-status', [ProductController::class, 'bulkUpdateStatus']);
});


