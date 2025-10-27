<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\InvoiceController;



Route::post('/invoice/pay-request', [InvoiceController::class, 'payRequest'])
->name('invoice.payRequest');

// middleware('auth:sanctum')->
// Route::group(function () {
   

//     Route::post('/invoice/confirm', [InvoiceController::class, 'confirm'])
//         ->name('invoice.confirm');
// });
