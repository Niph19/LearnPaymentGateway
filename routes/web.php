<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/payment', [PaymentController::class, 'index'])->name('demo-payment.index');
Route::post('/payment/pay', [PaymentController::class, 'pay'])->name('demo-payment.pay');
Route::post('/payment/check', [PaymentController::class, 'check'])->name('demo-payment.check');