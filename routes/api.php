<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;


Route::post('/login', [AuthController::class, 'login'])->name('login');;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/invoice/pay-request/{invoice}', [InvoiceController::class, 'payRequest'])
    ->name('invoices.payRequest');

    Route::post('/payment/confirm/{payment}', [InvoiceController::class, 'confirm'])
    ->name('payments.confirm');
});
