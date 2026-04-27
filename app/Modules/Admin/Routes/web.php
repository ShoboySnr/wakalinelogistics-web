<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\CommunicationsController;

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
        Route::get('/expenses/export', [DashboardController::class, 'exportStatement'])->name('admin.expenses.export');
        Route::get('/expenses/preview', [DashboardController::class, 'previewStatement'])->name('admin.expenses.preview');
        
        // Business Intelligence & Analytics
        Route::get('/business-intelligence', [DashboardController::class, 'businessIntelligence'])->name('admin.business-intelligence');
        
        // Rider Routes
        Route::get('/riders', [DashboardController::class, 'riders'])->name('admin.riders');
        Route::get('/riders/create', [DashboardController::class, 'createRider'])->name('admin.riders.create');
        Route::post('/riders', [DashboardController::class, 'storeRider'])->name('admin.riders.store');
        Route::get('/riders/{id}', [DashboardController::class, 'showRider'])->name('admin.riders.show');
        Route::get('/riders/{id}/edit', [DashboardController::class, 'editRider'])->name('admin.riders.edit');
        Route::put('/riders/{id}', [DashboardController::class, 'updateRider'])->name('admin.riders.update');
        Route::delete('/riders/{id}', [DashboardController::class, 'deleteRider'])->name('admin.riders.delete');
        Route::post('/riders/{id}/generate-share-link', [DashboardController::class, 'generateRouteShareLink'])->name('admin.riders.generate-share-link');
        Route::post('/riders/{id}/disable-share-link', [DashboardController::class, 'disableRouteShareLink'])->name('admin.riders.disable-share-link');
        Route::post('/riders/{id}/regenerate-code', [DashboardController::class, 'regenerateAccessCode'])->name('admin.riders.regenerate-code');
        Route::get('/riders/{id}/location', [DashboardController::class, 'getRiderLocation'])->name('admin.riders.location');
        Route::post('/orders/{id}/assign-rider', [DashboardController::class, 'assignRider'])->name('admin.orders.assign-rider');
        
        // Client Routes
        Route::get('/clients', [DashboardController::class, 'clients'])->name('admin.clients');
        Route::get('/clients/create', [DashboardController::class, 'createClient'])->name('admin.clients.create');
        Route::post('/clients', [DashboardController::class, 'storeClient'])->name('admin.clients.store');
        Route::get('/clients/{id}/edit', [DashboardController::class, 'editClient'])->name('admin.clients.edit');
        Route::put('/clients/{id}', [DashboardController::class, 'updateClient'])->name('admin.clients.update');
        Route::delete('/clients/{id}', [DashboardController::class, 'deleteClient'])->name('admin.clients.delete');
        Route::get('/clients/{id}/data', [DashboardController::class, 'getClientData'])->name('admin.clients.data');
        
        // Communications (subscriptions & contact messages)
        Route::get('/communications', [CommunicationsController::class, 'index'])->name('admin.communications');

        // Subscriptions
        Route::put('/communications/subscriptions/{id}', [CommunicationsController::class, 'updateSubscription'])->name('admin.communications.subscriptions.update');
        Route::delete('/communications/subscriptions/{id}', [CommunicationsController::class, 'deleteSubscription'])->name('admin.communications.subscriptions.delete');

        // Contact messages
        Route::put('/communications/messages/{id}', [CommunicationsController::class, 'updateMessage'])->name('admin.communications.messages.update');
        Route::delete('/communications/messages/{id}', [CommunicationsController::class, 'deleteMessage'])->name('admin.communications.messages.delete');
        
        // Job Applications Routes
        Route::get('/job-applications', [DashboardController::class, 'jobApplications'])->name('admin.job-applications');
        Route::get('/job-applications/{id}', [DashboardController::class, 'showJobApplication'])->name('admin.job-applications.show');
        Route::post('/job-applications/{id}/status', [DashboardController::class, 'updateJobApplicationStatus'])->name('admin.job-applications.update-status');
        Route::delete('/job-applications/{id}', [DashboardController::class, 'deleteJobApplication'])->name('admin.job-applications.delete');
    });
});
