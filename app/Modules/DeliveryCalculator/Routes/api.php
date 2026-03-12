<?php

use Illuminate\Support\Facades\Route;
use App\Modules\DeliveryCalculator\Controllers\DeliveryCalculatorApiController;

Route::prefix('wakalinelogistics/v1/metter')->name('api.metter.')->group(function () {
    Route::post('/calculate', [DeliveryCalculatorApiController::class, 'calculatePrice'])->name('calculate');
    Route::post('/quote', [DeliveryCalculatorApiController::class, 'quickQuote'])->name('quote');
    Route::get('/zones', [DeliveryCalculatorApiController::class, 'getZones'])->name('zones');
    Route::get('/pricing-rules', [DeliveryCalculatorApiController::class, 'getPricingRules'])->name('pricing');
    Route::get('/health', [DeliveryCalculatorApiController::class, 'healthCheck'])->name('health');
});
