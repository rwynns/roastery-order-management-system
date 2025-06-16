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

    // Custom routes
    Route::post('orders/{order}/payments', [OrderController::class, 'addPayment']);
    Route::get('reports/sales', [ReportController::class, 'salesReport']);
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);

    // Addtional product routes
    Route::get('products/low-stock',[ProductController::class, 'lowStock']);
    Route::post('products/bulk-status', [ProductController::class, 'bulkUpdateStatus']);
});


