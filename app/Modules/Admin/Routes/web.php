<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Admin\Controllers\AdminAuthController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\OrderController;
use App\Modules\Admin\Controllers\RiderController;
use App\Modules\Admin\Controllers\ClientController;
use App\Modules\Admin\Controllers\BikeController;
use App\Modules\Admin\Controllers\ExpenseController;
use App\Modules\Admin\Controllers\SettingsController;
use App\Modules\Admin\Controllers\CommunicationsController;
use App\Modules\Admin\Controllers\UserController;
use App\Modules\Admin\Controllers\TransactionController;
use App\Modules\Admin\Controllers\CreditsController;
use App\Modules\Admin\Controllers\HelpController;
use App\Modules\Admin\Controllers\InvoiceController;

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
        
        // Order Routes (moved to OrderController)
        Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('admin.orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('admin.orders.store');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
        Route::get('/orders/{id}/edit', [OrderController::class, 'edit'])->name('admin.orders.edit');
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.update-status');
        Route::get('/orders/{id}/invoice', [OrderController::class, 'generateInvoice'])->name('admin.orders.invoice');
        Route::put('/orders/{id}', [OrderController::class, 'update'])->name('admin.orders.update');
        Route::delete('/orders/{id}', [OrderController::class, 'destroy'])->name('admin.orders.delete');
        Route::post('/orders/{id}/assign-rider', [OrderController::class, 'assignRider'])->name('admin.orders.assign-rider');
        Route::get('/remittance/pod-data', [OrderController::class, 'getPodRemittanceData'])->name('admin.remittance.pod-data');

        // Profile Routes
        Route::get('/profile', [DashboardController::class, 'profile'])->name('admin.profile');
        Route::put('/profile', [DashboardController::class, 'updateProfile'])->name('admin.profile.update');
        Route::put('/profile/password', [DashboardController::class, 'updatePassword'])->name('admin.profile.password');
        
        // Settings Routes
        Route::get('/settings', [SettingsController::class, 'index'])->name('admin.settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::get('/settings/batch-discount', [SettingsController::class, 'batchDiscount'])->name('admin.settings.batch-discount');
        Route::post('/settings/batch-discount', [SettingsController::class, 'updateBatchDiscount'])->name('admin.settings.batch-discount.update');
        Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('admin.settings.clear-cache');
        
        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('admin.expenses');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
        Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])->name('admin.expenses.delete');
        Route::get('/expenses/export', [ExpenseController::class, 'exportStatement'])->name('admin.expenses.export');
        Route::get('/expenses/preview', [ExpenseController::class, 'previewStatement'])->name('admin.expenses.preview');
        
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
        Route::get('/credits/packages/{id}', [CreditsController::class, 'showPackage'])->name('admin.credits.packages.show');
        Route::get('/credits/packages/{id}/edit', [CreditsController::class, 'editPackage'])->name('admin.credits.packages.edit');
        Route::put('/credits/packages/{id}', [CreditsController::class, 'updatePackage'])->name('admin.credits.packages.update');
        Route::delete('/credits/packages/{id}', [CreditsController::class, 'deletePackage'])->name('admin.credits.packages.delete');
        Route::get('/credits/settings', [CreditsController::class, 'settings'])->name('admin.credits.settings');
        Route::put('/credits/settings', [CreditsController::class, 'updateSettings'])->name('admin.credits.settings.update');
        Route::post('/credits/clients/{id}/adjust', [CreditsController::class, 'adjustCredits'])->name('admin.credits.adjust');
        Route::get('/credits/clients/{id}/history', [CreditsController::class, 'clientHistory'])->name('admin.credits.client-history');
        
        // Business Intelligence — AI analyst
        Route::get('/business-intelligence', [DashboardController::class, 'businessIntelligence'])->name('admin.business-intelligence');
        Route::post('/business-intelligence/ask', [DashboardController::class, 'askAnalyst'])->name('admin.business-intelligence.ask');
        Route::post('/business-intelligence/confirm', [DashboardController::class, 'confirmAnalystActions'])->name('admin.business-intelligence.confirm');
        Route::post('/business-intelligence/reset', [DashboardController::class, 'resetAnalyst'])->name('admin.business-intelligence.reset');
        
        // Rider Routes (moved to RiderController)
        Route::get('/riders', [RiderController::class, 'index'])->name('admin.riders');
        Route::get('/riders/create', [RiderController::class, 'create'])->name('admin.riders.create');
        Route::post('/riders', [RiderController::class, 'store'])->name('admin.riders.store');
        Route::get('/riders/{id}', [RiderController::class, 'show'])->name('admin.riders.show');
        Route::get('/riders/{id}/edit', [RiderController::class, 'edit'])->name('admin.riders.edit');
        Route::put('/riders/{id}', [RiderController::class, 'update'])->name('admin.riders.update');
        Route::delete('/riders/{id}', [RiderController::class, 'destroy'])->name('admin.riders.delete');
        Route::post('/riders/{id}/generate-share-link', [RiderController::class, 'generateShareLink'])->name('admin.riders.generate-share-link');
        Route::post('/riders/{id}/disable-share-link', [RiderController::class, 'disableShareLink'])->name('admin.riders.disable-share-link');
        Route::post('/riders/{id}/regenerate-code', [RiderController::class, 'regenerateCode'])->name('admin.riders.regenerate-code');
        Route::get('/riders/{id}/location', [RiderController::class, 'getLocation'])->name('admin.riders.location');
        
        // Bike Routes (moved to BikeController)
        Route::get('/bikes', [BikeController::class, 'index'])->name('admin.bikes');
        Route::get('/bikes/create', [BikeController::class, 'create'])->name('admin.bikes.create');
        Route::post('/bikes', [BikeController::class, 'store'])->name('admin.bikes.store');
        Route::get('/bikes/{id}', [BikeController::class, 'show'])->name('admin.bikes.show');
        Route::get('/bikes/{id}/edit', [BikeController::class, 'edit'])->name('admin.bikes.edit');
        Route::put('/bikes/{id}', [BikeController::class, 'update'])->name('admin.bikes.update');
        Route::delete('/bikes/{id}', [BikeController::class, 'destroy'])->name('admin.bikes.delete');
        Route::post('/bikes/{id}/assign-rider', [BikeController::class, 'assignRider'])->name('admin.bikes.assign-rider');
        Route::post('/bikes/{id}/unassign-rider', [BikeController::class, 'unassignRider'])->name('admin.bikes.unassign-rider');
        
        // Client Routes (moved to ClientController)
        Route::get('/clients', [ClientController::class, 'index'])->name('admin.clients');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('admin.clients.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('admin.clients.store');
        Route::get('/clients/{id}', [ClientController::class, 'show'])->name('admin.clients.show');
        Route::get('/clients/{id}/edit', [ClientController::class, 'edit'])->name('admin.clients.edit');
        Route::put('/clients/{id}', [ClientController::class, 'update'])->name('admin.clients.update');
        Route::delete('/clients/{id}', [ClientController::class, 'destroy'])->name('admin.clients.delete');
        Route::get('/clients/{id}/data', [ClientController::class, 'getData'])->name('admin.clients.data');
        Route::post('/clients/{id}/generate-share-link', [ClientController::class, 'generateShareLink'])->name('admin.clients.generate-share-link');
        Route::post('/clients/{id}/disable-share-link', [ClientController::class, 'disableShareLink'])->name('admin.clients.disable-share-link');
        Route::post('/clients/{id}/generate-api-key', [ClientController::class, 'generateApiKey'])->name('admin.clients.generate-api-key');
        Route::post('/clients/{id}/regenerate-api-key', [ClientController::class, 'regenerateApiKey'])->name('admin.clients.regenerate-api-key');
        Route::post('/clients/{id}/enable-api-access', [ClientController::class, 'enableApiAccess'])->name('admin.clients.enable-api-access');
        Route::post('/clients/{id}/disable-api-access', [ClientController::class, 'disableApiAccess'])->name('admin.clients.disable-api-access');
        Route::post('/clients/{id}/subscribe', [ClientController::class, 'subscribe'])->name('admin.clients.subscribe');
        Route::post('/clients/{id}/add-credits', [ClientController::class, 'addCredits'])->name('admin.clients.add-credits');
        Route::post('/clients/{id}/deduct-credits', [ClientController::class, 'deductCredits'])->name('admin.clients.deduct-credits');
        Route::post('/clients/{id}/verify-email', [ClientController::class, 'verifyEmail'])->name('admin.clients.verify-email');
        Route::post('/clients/{id}/mark-remitted', [ClientController::class, 'markRemitted'])->name('admin.clients.mark-remitted');
        Route::post('/clients/{id}/toggle-pod-remittance', [ClientController::class, 'togglePodRemittance'])->name('admin.clients.toggle-pod-remittance');
        Route::post('/clients/{id}/stocks', [ClientController::class, 'updateStocks'])->name('admin.clients.update-stocks');

        // Client invoices
        Route::post('/clients/{id}/invoices', [InvoiceController::class, 'store'])->name('admin.invoices.store');
        Route::get('/invoices/{id}/preview', [InvoiceController::class, 'preview'])->name('admin.invoices.preview');
        Route::get('/invoices/{id}/download', [InvoiceController::class, 'download'])->name('admin.invoices.download');
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('admin.invoices.destroy');

        // Communications (subscriptions & contact messages)
        Route::get('/communications', [CommunicationsController::class, 'index'])->name('admin.communications');

        // Waitlist management
        Route::get('/waitlist', [CommunicationsController::class, 'waitlist'])->name('admin.waitlist');
        Route::post('/waitlist/send-sample-email', [CommunicationsController::class, 'sendSampleEmail'])->name('admin.waitlist.send-sample-email');
        Route::post('/waitlist/{clientId}/send-activation-email', [CommunicationsController::class, 'sendActivationEmail'])->name('admin.waitlist.send-activation-email');
        Route::post('/waitlist/send-launch-emails', [CommunicationsController::class, 'sendLaunchEmails'])->name('admin.waitlist.send-launch-emails');

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
        
        // Help Center Routes
        Route::get('/help', [HelpController::class, 'tickets'])->name('admin.help');
        Route::get('/help/faqs', [HelpController::class, 'faqs'])->name('admin.help.faqs');
        Route::get('/help/faqs/create', [HelpController::class, 'createFaq'])->name('admin.help.faqs.create');
        Route::post('/help/faqs', [HelpController::class, 'storeFaq'])->name('admin.help.faqs.store');
        Route::get('/help/faqs/{id}/edit', [HelpController::class, 'editFaq'])->name('admin.help.faqs.edit');
        Route::put('/help/faqs/{id}', [HelpController::class, 'updateFaq'])->name('admin.help.faqs.update');
        Route::delete('/help/faqs/{id}', [HelpController::class, 'deleteFaq'])->name('admin.help.faqs.delete');
        Route::get('/help/tickets', [HelpController::class, 'tickets'])->name('admin.help.tickets');
        Route::get('/help/tickets/{id}', [HelpController::class, 'showTicket'])->name('admin.help.tickets.show');
        Route::post('/help/tickets/{id}/reply', [HelpController::class, 'replyTicket'])->name('admin.help.tickets.reply');
        Route::post('/help/tickets/{id}/status', [HelpController::class, 'updateTicketStatus'])->name('admin.help.tickets.status');
        Route::post('/help/tickets/{id}/assign', [HelpController::class, 'assignTicket'])->name('admin.help.tickets.assign');
        Route::delete('/help/tickets/{id}', [HelpController::class, 'deleteTicket'])->name('admin.help.tickets.delete');

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
