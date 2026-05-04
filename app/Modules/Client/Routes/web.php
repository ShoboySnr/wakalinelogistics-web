<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Client\Controllers\ClientAuthController;
use App\Modules\Client\Controllers\ClientDashboardController;

// Client Routes
Route::prefix('client')->group(function () {
    // Client Authentication Routes
    Route::get('/login', [ClientAuthController::class, 'showLoginForm'])->name('client.login');
    Route::post('/login', [ClientAuthController::class, 'login'])->name('client.login.submit');
    Route::post('/logout', [ClientAuthController::class, 'logout'])->name('client.logout');

    // Protected Client Routes
    Route::middleware(['client.auth'])->group(function () {
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
        
        // Orders
        Route::get('/orders', [ClientDashboardController::class, 'orders'])->name('client.orders');
        Route::get('/orders/create', [ClientDashboardController::class, 'createOrder'])->name('client.orders.create');
        Route::post('/orders', [ClientDashboardController::class, 'storeOrder'])->name('client.orders.store');
        Route::get('/orders/{id}', [ClientDashboardController::class, 'showOrder'])->name('client.orders.show');
        
        // Profile
        Route::get('/profile', [ClientDashboardController::class, 'profile'])->name('client.profile');
        Route::put('/profile', [ClientDashboardController::class, 'updateProfile'])->name('client.profile.update');
        Route::put('/profile/password', [ClientDashboardController::class, 'updatePassword'])->name('client.profile.password');
    });
});
