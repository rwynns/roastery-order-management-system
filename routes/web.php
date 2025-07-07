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

Route::get('/login', function () {
    return view('login');
});
