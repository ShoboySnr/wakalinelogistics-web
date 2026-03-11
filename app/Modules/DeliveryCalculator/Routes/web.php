<?php

use Illuminate\Support\Facades\Route;
use App\Modules\DeliveryCalculator\Controllers\DeliveryCalculatorController;

Route::prefix('meter')->name('meter.')->group(function () {
    Route::get('/', [DeliveryCalculatorController::class, 'index'])->name('index');
    Route::post('/calculate', [DeliveryCalculatorController::class, 'calculate'])->name('calculate');
});
