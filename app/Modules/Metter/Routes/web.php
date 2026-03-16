<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Metter\Controllers\MetterController;

Route::middleware(['web', 'auth'])->prefix('super-admin/metter')->name('metter.')->group(function () {
    Route::get('/settings', [MetterController::class, 'showSettings'])->name('settings');
    Route::put('/settings', [MetterController::class, 'updateSettings'])->name('settings.update');
    Route::post('/features/{id}/toggle', [MetterController::class, 'toggleFeature'])->name('features.toggle');
});
