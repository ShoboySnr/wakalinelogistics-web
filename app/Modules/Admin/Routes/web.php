<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\DashboardController;

// Admin Routes
Route::prefix('super-admin')->group(function () {
    // Authentication Routes
    Route::get('/', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Protected Admin Routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/orders', [DashboardController::class, 'orders'])->name('admin.orders');
        Route::get('/orders/create', [DashboardController::class, 'createOrder'])->name('admin.orders.create');
        Route::post('/orders', [DashboardController::class, 'storeOrder'])->name('admin.orders.store');
        Route::get('/orders/{id}', [DashboardController::class, 'showOrder'])->name('admin.orders.show');
        Route::get('/orders/{id}/edit', [DashboardController::class, 'editOrder'])->name('admin.orders.edit');
        Route::post('/orders/{id}/status', [DashboardController::class, 'updateOrderStatus'])->name('admin.orders.update-status');
        Route::get('/orders/{id}/invoice', [DashboardController::class, 'generateInvoice'])->name('admin.orders.invoice');
        Route::put('/orders/{id}', [DashboardController::class, 'updateOrder'])->name('admin.orders.update');
        Route::delete('/orders/{id}', [DashboardController::class, 'deleteOrder'])->name('admin.orders.delete');
        
        // Profile Routes
        Route::get('/profile', [DashboardController::class, 'profile'])->name('admin.profile');
        Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('admin.profile.update');
        Route::put('/profile/password', [DashboardController::class, 'updatePassword'])->name('admin.profile.password');
        
        // Settings Routes
        Route::get('/settings', [DashboardController::class, 'settings'])->name('admin.settings');
        Route::put('/settings', [DashboardController::class, 'updateSettings'])->name('admin.settings.update');
        
        // Expenses
        Route::get('/expenses', [DashboardController::class, 'expenses'])->name('admin.expenses');
        Route::post('/expenses', [DashboardController::class, 'storeExpense'])->name('admin.expenses.store');
        Route::delete('/expenses/{id}', [DashboardController::class, 'deleteExpense'])->name('admin.expenses.delete');
        
        // Rider Routes
        Route::get('/riders', [DashboardController::class, 'riders'])->name('admin.riders');
        Route::get('/riders/create', [DashboardController::class, 'createRider'])->name('admin.riders.create');
        Route::post('/riders', [DashboardController::class, 'storeRider'])->name('admin.riders.store');
        Route::get('/riders/{id}', [DashboardController::class, 'showRider'])->name('admin.riders.show');
        Route::get('/riders/{id}/edit', [DashboardController::class, 'editRider'])->name('admin.riders.edit');
        Route::put('/riders/{id}', [DashboardController::class, 'updateRider'])->name('admin.riders.update');
        Route::delete('/riders/{id}', [DashboardController::class, 'deleteRider'])->name('admin.riders.delete');
        Route::post('/orders/{id}/assign-rider', [DashboardController::class, 'assignRider'])->name('admin.orders.assign-rider');
    });
});
