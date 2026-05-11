<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\CommunicationsController;
use App\Modules\Admin\Controllers\UserController;
use App\Modules\Admin\Controllers\TransactionController;
use App\Modules\Admin\Controllers\CreditsController;

// Admin Login at root
Route::get('/', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

// Admin Routes
Route::prefix('super-admin')->group(function () {
    // Logout Route
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
        
        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index'])->name('admin.transactions');
        Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('admin.transactions.show');
        Route::post('/transactions/{id}/approve', [TransactionController::class, 'approve'])->name('admin.transactions.approve');
        Route::post('/transactions/{id}/reject', [TransactionController::class, 'reject'])->name('admin.transactions.reject');
        Route::post('/transactions/{id}/reverse', [TransactionController::class, 'reverse'])->name('admin.transactions.reverse');
        
        // Credits & Subscriptions
        Route::get('/credits', [CreditsController::class, 'index'])->name('admin.credits');
        Route::get('/credits/plans', [CreditsController::class, 'plans'])->name('admin.credits.plans');
        Route::get('/credits/plans/create', [CreditsController::class, 'createPlan'])->name('admin.credits.plans.create');
        Route::post('/credits/plans', [CreditsController::class, 'storePlan'])->name('admin.credits.plans.store');
        Route::get('/credits/plans/{id}/edit', [CreditsController::class, 'editPlan'])->name('admin.credits.plans.edit');
        Route::put('/credits/plans/{id}', [CreditsController::class, 'updatePlan'])->name('admin.credits.plans.update');
        Route::delete('/credits/plans/{id}', [CreditsController::class, 'deletePlan'])->name('admin.credits.plans.delete');
        Route::get('/credits/packages', [CreditsController::class, 'packages'])->name('admin.credits.packages');
        Route::get('/credits/packages/create', [CreditsController::class, 'createPackage'])->name('admin.credits.packages.create');
        Route::post('/credits/packages', [CreditsController::class, 'storePackage'])->name('admin.credits.packages.store');
        Route::get('/credits/packages/{id}/edit', [CreditsController::class, 'editPackage'])->name('admin.credits.packages.edit');
        Route::put('/credits/packages/{id}', [CreditsController::class, 'updatePackage'])->name('admin.credits.packages.update');
        Route::delete('/credits/packages/{id}', [CreditsController::class, 'deletePackage'])->name('admin.credits.packages.delete');
        Route::get('/credits/settings', [CreditsController::class, 'settings'])->name('admin.credits.settings');
        Route::put('/credits/settings', [CreditsController::class, 'updateSettings'])->name('admin.credits.settings.update');
        Route::post('/credits/clients/{id}/adjust', [CreditsController::class, 'adjustCredits'])->name('admin.credits.adjust');
        Route::get('/credits/clients/{id}/history', [CreditsController::class, 'clientHistory'])->name('admin.credits.client-history');
        
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
        
        // Bike Routes
        Route::get('/bikes', [DashboardController::class, 'bikes'])->name('admin.bikes');
        Route::get('/bikes/create', [DashboardController::class, 'createBike'])->name('admin.bikes.create');
        Route::post('/bikes', [DashboardController::class, 'storeBike'])->name('admin.bikes.store');
        Route::get('/bikes/{id}', [DashboardController::class, 'showBike'])->name('admin.bikes.show');
        Route::get('/bikes/{id}/edit', [DashboardController::class, 'editBike'])->name('admin.bikes.edit');
        Route::put('/bikes/{id}', [DashboardController::class, 'updateBike'])->name('admin.bikes.update');
        Route::delete('/bikes/{id}', [DashboardController::class, 'deleteBike'])->name('admin.bikes.delete');
        Route::post('/bikes/{id}/assign-rider', [DashboardController::class, 'assignBikeToRider'])->name('admin.bikes.assign-rider');
        Route::post('/bikes/{id}/unassign-rider', [DashboardController::class, 'unassignBikeFromRider'])->name('admin.bikes.unassign-rider');
        
        // Client Routes
        Route::get('/clients', [DashboardController::class, 'clients'])->name('admin.clients');
        Route::get('/clients/create', [DashboardController::class, 'createClient'])->name('admin.clients.create');
        Route::post('/clients', [DashboardController::class, 'storeClient'])->name('admin.clients.store');
        Route::get('/clients/{id}', [DashboardController::class, 'showClient'])->name('admin.clients.show');
        Route::get('/clients/{id}/edit', [DashboardController::class, 'editClient'])->name('admin.clients.edit');
        Route::put('/clients/{id}', [DashboardController::class, 'updateClient'])->name('admin.clients.update');
        Route::delete('/clients/{id}', [DashboardController::class, 'deleteClient'])->name('admin.clients.delete');
        Route::get('/clients/{id}/data', [DashboardController::class, 'getClientData'])->name('admin.clients.data');
        Route::post('/clients/{id}/toggle-dashboard', [DashboardController::class, 'toggleClientDashboard'])->name('admin.clients.toggle-dashboard');
        Route::post('/clients/{id}/set-password', [DashboardController::class, 'setClientPassword'])->name('admin.clients.set-password');
        Route::post('/clients/{id}/generate-share-link', [DashboardController::class, 'generateClientShareLink'])->name('admin.clients.generate-share-link');
        Route::post('/clients/{id}/disable-share-link', [DashboardController::class, 'disableClientShareLink'])->name('admin.clients.disable-share-link');
        Route::post('/clients/{id}/generate-api-key', [DashboardController::class, 'generateApiKey'])->name('admin.clients.generate-api-key');
        Route::post('/clients/{id}/regenerate-api-key', [DashboardController::class, 'regenerateApiKey'])->name('admin.clients.regenerate-api-key');
        Route::post('/clients/{id}/enable-api-access', [DashboardController::class, 'enableApiAccess'])->name('admin.clients.enable-api-access');
        Route::post('/clients/{id}/disable-api-access', [DashboardController::class, 'disableApiAccess'])->name('admin.clients.disable-api-access');
        
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
        
        // User Management Routes
        Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{id}', [UserController::class, 'show'])->name('admin.users.show');
        Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::put('/users/{id}/password', [UserController::class, 'updatePassword'])->name('admin.users.update-password');
        Route::post('/users/{id}/toggle-admin', [UserController::class, 'toggleAdmin'])->name('admin.users.toggle-admin');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });
});
