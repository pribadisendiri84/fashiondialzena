<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderReturnController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StockInController;
use App\Http\Controllers\StorefrontController;
use App\Http\Middleware\AdminAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'index'])->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(AdminAuthenticate::class)->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('pembukuan', [DashboardController::class, 'ledger'])->name('ledger');
        Route::resource('sales', OrderController::class)->parameters(['sales' => 'order'])->only(['index', 'store', 'destroy']);
        Route::resource('returns', OrderReturnController::class)
            ->parameters(['returns' => 'orderReturn'])
            ->only(['index', 'store', 'destroy']);
        Route::resource('stock-ins', StockInController::class)->parameters(['stock-ins' => 'stockIn'])->only(['index', 'store', 'destroy']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');
        Route::resource('categories', CategoryController::class)->except(['show', 'create', 'edit']);
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
