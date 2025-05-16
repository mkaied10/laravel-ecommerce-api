<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/webhook', [OrderController::class, 'handleStripeWebhook']);
Route::get('/payment/success', [OrderController::class, 'handlePaymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [OrderController::class, 'handlePaymentCancel'])->name('payment.cancel');