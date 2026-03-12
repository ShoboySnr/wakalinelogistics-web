<?php

use Illuminate\Support\Facades\Route;
use App\Modules\DeliveryCalculator\Controllers\DeliveryCalculatorController;

Route::prefix('metter')->name('metter.')->group(function () {
    Route::get('/', [DeliveryCalculatorController::class, 'index'])->name('index');
    Route::post('/calculate', [DeliveryCalculatorController::class, 'calculate'])->name('calculate');
});
