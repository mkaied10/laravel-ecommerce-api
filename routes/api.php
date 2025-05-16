<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


Route::middleware('Localization')->group(function () {
    // --------------------- Authentication Routes ---------------------

    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    
    // Google OAuth
    Route::get('/login/google', [AuthController::class, 'redirectToGoogle'])->name('login.google');
    Route::get('/login/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('login.google.callback');
    
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])
        ->name('verification.verify');
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
        ->name('verification.resend');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        
        
        Route::middleware(['verified'])->get('/user', [AuthController::class, 'show'])->name('user.show');
    });


    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::middleware(['verified'])->get('/user', [AuthController::class, 'show'])->name('auth.user');
    });

    // --------------------- Category Routes ---------------------

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('categories.show');
    });

    Route::middleware(['auth:sanctum', 'verified', 'admin'])->group(function () {
        Route::apiResource('categories', CategoryController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters(['categories' => 'id']);
    });

    // --------------------- Product Routes ---------------------

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/{id}', [ProductController::class, 'show'])->name('products.show');
    });


    Route::middleware(['auth:sanctum', 'verified', 'admin'])->group(function () {
        Route::apiResource('products', ProductController::class)
            ->only(['store', 'update', 'destroy'])
            ->parameters(['products' => 'id']);
    });

    // --------------------- Cart & Order Routes ---------------------

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        // Cart Routes
        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('cart.index');
            Route::post('/', [CartController::class, 'store'])->name('cart.store');
            Route::put('/{rowId}', [CartController::class, 'update'])->name('cart.update');
            Route::delete('/{rowId}', [CartController::class, 'destroy'])->name('cart.destroy');
            Route::post('/clear', [CartController::class, 'clear'])->name('cart.clear');
        });

        // Order Routes
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/', [OrderController::class, 'store'])->name('orders.store');
            Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.update.status');
            Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        });
    });
});