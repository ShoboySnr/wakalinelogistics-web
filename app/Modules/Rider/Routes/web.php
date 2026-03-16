<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Rider\Controllers\RiderAuthController;
use App\Modules\Rider\Controllers\RiderDashboardController;

// Rider Authentication Routes
Route::prefix('rider')->group(function () {
    Route::get('/login', [RiderAuthController::class, 'showLogin'])->name('rider.login');
    Route::post('/login', [RiderAuthController::class, 'login'])->name('rider.login.submit');
    Route::post('/logout', [RiderAuthController::class, 'logout'])->name('rider.logout');
});

// Rider Dashboard Routes (Protected)
Route::prefix('rider')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', [RiderDashboardController::class, 'index'])->name('rider.dashboard');
    Route::get('/orders', [RiderDashboardController::class, 'orders'])->name('rider.orders');
    Route::get('/orders/{id}', [RiderDashboardController::class, 'showOrder'])->name('rider.orders.show');
    Route::post('/orders/{id}/update-status', [RiderDashboardController::class, 'updateOrderStatus'])->name('rider.orders.update-status');
    Route::get('/profile', [RiderDashboardController::class, 'profile'])->name('rider.profile');
    Route::put('/profile', [RiderDashboardController::class, 'updateProfile'])->name('rider.profile.update');
});
