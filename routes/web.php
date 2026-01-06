<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/payment/callback', \App\Http\Controllers\PaymentCallbackController::class)->name('payment.callback');
