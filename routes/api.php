<?php

use App\Http\Controllers\Api\V1\PublicCashController;
use App\Http\Controllers\Api\V1\PublicExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/public')->middleware('throttle:60,1')->name('api.v1.public.')->group(function () {
    Route::get('/summary', [PublicCashController::class, 'summary'])->name('summary');
    Route::get('/ledger', [PublicCashController::class, 'ledger'])->name('ledger');
    Route::get('/anchors', [PublicCashController::class, 'anchors'])->name('anchors');
    Route::get('/expenses', [PublicExpenseController::class, 'index'])->name('expenses');
});
