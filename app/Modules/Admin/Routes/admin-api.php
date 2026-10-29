<?php

use App\Modules\Admin\Controllers\Api\AuthController;
use App\Modules\Admin\Controllers\Api\BusinessIntelligenceController;
use App\Modules\Admin\Controllers\Api\BikeController;
use App\Modules\Admin\Controllers\Api\ClientController;
use App\Modules\Admin\Controllers\Api\CommunicationsController;
use App\Modules\Admin\Controllers\Api\CreditController;
use App\Modules\Admin\Controllers\Api\DashboardController;
use App\Modules\Admin\Controllers\Api\HelpController;
use App\Modules\Admin\Controllers\Api\ExpenseController;
use App\Modules\Admin\Controllers\Api\InvoiceController;
use App\Modules\Admin\Controllers\Api\JobApplicationController;
use App\Modules\Admin\Controllers\Api\SettingsController;
use App\Modules\Admin\Controllers\Api\UserController;
use App\Modules\Admin\Controllers\Api\OrderController;
use App\Modules\Admin\Controllers\Api\TransactionController;
use App\Modules\Admin\Controllers\Api\RiderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/v1')->name('api.admin.')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');

    Route::middleware(['auth:sanctum', 'admin.api'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::put('auth/profile', [AuthController::class, 'updateProfile'])->name('auth.profile');
        Route::put('auth/password', [AuthController::class, 'updatePassword'])->name('auth.password');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('orders/form-options', [OrderController::class, 'formOptions'])->name('orders.form-options');
        Route::get('orders/remittance', [OrderController::class, 'remittanceData'])->name('orders.remittance');
        Route::post('orders/remittance/mark-remitted', [OrderController::class, 'markRemitted'])->name('orders.mark-remitted');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('orders/{id}', [OrderController::class, 'show'])->whereNumber('id')->name('orders.show');
        Route::post('orders/{id}', [OrderController::class, 'update'])->whereNumber('id')->name('orders.update');
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->whereNumber('id')->name('orders.status');
        Route::patch('orders/{id}/rider', [OrderController::class, 'assignRider'])->whereNumber('id')->name('orders.rider');
        Route::delete('orders/{id}', [OrderController::class, 'destroy'])->whereNumber('id')->name('orders.destroy');

        Route::get('clients/form-options', [ClientController::class, 'formOptions'])->name('clients.form-options');
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::prefix('clients/{id}')->whereNumber('id')->name('clients.')->group(function () {
            Route::get('/', [ClientController::class, 'show'])->name('show');
            Route::put('/', [ClientController::class, 'update'])->name('update');
            Route::delete('/', [ClientController::class, 'destroy'])->name('destroy');
            Route::get('orders', [ClientController::class, 'orders'])->name('orders');
            Route::post('subscribe', [ClientController::class, 'subscribe'])->name('subscribe');
            Route::post('credits', [ClientController::class, 'adjustCredits'])->name('credits');
            Route::post('verify-email', [ClientController::class, 'verifyEmail'])->name('verify-email');
            Route::put('stocks', [ClientController::class, 'updateStocks'])->name('stocks');
            Route::post('toggle-remittance', [ClientController::class, 'togglePodRemittance'])->name('toggle-remittance');
            Route::post('share-link', [ClientController::class, 'shareLink'])->name('share-link');
            Route::post('api-access', [ClientController::class, 'apiAccess'])->name('api-access');
            Route::post('invoices', [ClientController::class, 'storeInvoice'])->name('invoices.store');
        });

        Route::get('riders/form-options', [RiderController::class, 'formOptions'])->name('riders.form-options');
        Route::get('riders', [RiderController::class, 'index'])->name('riders.index');
        Route::post('riders', [RiderController::class, 'store'])->name('riders.store');
        Route::prefix('riders/{id}')->whereNumber('id')->name('riders.')->group(function () {
            Route::get('/', [RiderController::class, 'show'])->name('show');
            Route::post('/', [RiderController::class, 'update'])->name('update');
            Route::delete('/', [RiderController::class, 'destroy'])->name('destroy');
            Route::get('orders', [RiderController::class, 'orders'])->name('orders');
            Route::get('location', [RiderController::class, 'location'])->name('location');
            Route::post('share-link', [RiderController::class, 'shareLink'])->name('share-link');
            Route::post('regenerate-code', [RiderController::class, 'regenerateCode'])->name('regenerate-code');
        });

        Route::get('bikes/form-options', [BikeController::class, 'formOptions'])->name('bikes.form-options');
        Route::get('bikes', [BikeController::class, 'index'])->name('bikes.index');
        Route::post('bikes', [BikeController::class, 'store'])->name('bikes.store');
        Route::prefix('bikes/{id}')->whereNumber('id')->name('bikes.')->group(function () {
            Route::get('/', [BikeController::class, 'show'])->name('show');
            Route::post('/', [BikeController::class, 'update'])->name('update');
            Route::delete('/', [BikeController::class, 'destroy'])->name('destroy');
            Route::post('assign-rider', [BikeController::class, 'assignRider'])->name('assign-rider');
        });

        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('transactions/{id}', [TransactionController::class, 'show'])->whereNumber('id')->name('transactions.show');
        Route::post('transactions/{id}/approve', [TransactionController::class, 'approve'])->whereNumber('id')->name('transactions.approve');
        Route::post('transactions/{id}/reject', [TransactionController::class, 'reject'])->whereNumber('id')->name('transactions.reject');
        Route::post('transactions/{id}/reverse', [TransactionController::class, 'reverse'])->whereNumber('id')->name('transactions.reverse');

        Route::get('expenses/form-options', [ExpenseController::class, 'formOptions'])->name('expenses.form-options');
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('expenses/statement', [ExpenseController::class, 'statement'])->name('expenses.statement');
        Route::delete('expenses/{id}', [ExpenseController::class, 'destroy'])->whereNumber('id')->name('expenses.destroy');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{id}', [UserController::class, 'show'])->whereNumber('id')->name('users.show');
        Route::put('users/{id}', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
        Route::put('users/{id}/password', [UserController::class, 'updatePassword'])->whereNumber('id')->name('users.password');
        Route::post('users/{id}/toggle-admin', [UserController::class, 'toggleAdmin'])->whereNumber('id')->name('users.toggle-admin');
        Route::delete('users/{id}', [UserController::class, 'destroy'])->whereNumber('id')->name('users.destroy');

        Route::get('job-applications', [JobApplicationController::class, 'index'])->name('job-applications.index');
        Route::get('job-applications/{id}', [JobApplicationController::class, 'show'])->whereNumber('id')->name('job-applications.show');
        Route::post('job-applications/{id}/status', [JobApplicationController::class, 'updateStatus'])->whereNumber('id')->name('job-applications.status');
        Route::delete('job-applications/{id}', [JobApplicationController::class, 'destroy'])->whereNumber('id')->name('job-applications.destroy');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/batch-discount', [SettingsController::class, 'updateBatchDiscount'])->name('settings.batch-discount');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');

        Route::prefix('business-intelligence')->name('bi.')->group(function () {
            Route::get('/', [BusinessIntelligenceController::class, 'bootstrap'])->name('bootstrap');
            Route::post('ask', [BusinessIntelligenceController::class, 'ask'])->name('ask');
            Route::post('confirm', [BusinessIntelligenceController::class, 'confirm'])->name('confirm');
            Route::post('reset', [BusinessIntelligenceController::class, 'reset'])->name('reset');
        });

        Route::prefix('communications')->name('communications.')->group(function () {
            Route::get('subscriptions', [CommunicationsController::class, 'subscriptions'])->name('subscriptions');
            Route::put('subscriptions/{id}', [CommunicationsController::class, 'updateSubscription'])->whereNumber('id')->name('subscriptions.update');
            Route::delete('subscriptions/{id}', [CommunicationsController::class, 'deleteSubscription'])->whereNumber('id')->name('subscriptions.destroy');
            Route::get('messages', [CommunicationsController::class, 'messages'])->name('messages');
            Route::put('messages/{id}', [CommunicationsController::class, 'updateMessage'])->whereNumber('id')->name('messages.update');
            Route::delete('messages/{id}', [CommunicationsController::class, 'deleteMessage'])->whereNumber('id')->name('messages.destroy');
            Route::get('waitlist', [CommunicationsController::class, 'waitlist'])->name('waitlist');
            Route::post('waitlist/send-sample', [CommunicationsController::class, 'sendSampleEmail'])->name('waitlist.sample');
            Route::post('waitlist/send-launch', [CommunicationsController::class, 'sendLaunchEmails'])->name('waitlist.launch');
            Route::post('waitlist/{id}/send-activation', [CommunicationsController::class, 'sendActivationEmail'])->whereNumber('id')->name('waitlist.activation');
        });

        Route::prefix('credits')->name('credits.')->group(function () {
            Route::get('overview', [CreditController::class, 'overview'])->name('overview');
            Route::get('plans', [CreditController::class, 'plans'])->name('plans');
            Route::post('plans', [CreditController::class, 'storePlan'])->name('plans.store');
            Route::put('plans/{id}', [CreditController::class, 'updatePlan'])->whereNumber('id')->name('plans.update');
            Route::delete('plans/{id}', [CreditController::class, 'deletePlan'])->whereNumber('id')->name('plans.destroy');
            Route::get('packages', [CreditController::class, 'packages'])->name('packages');
            Route::post('packages', [CreditController::class, 'storePackage'])->name('packages.store');
            Route::put('packages/{id}', [CreditController::class, 'updatePackage'])->whereNumber('id')->name('packages.update');
            Route::delete('packages/{id}', [CreditController::class, 'deletePackage'])->whereNumber('id')->name('packages.destroy');
            Route::get('settings', [CreditController::class, 'settings'])->name('settings');
            Route::put('settings', [CreditController::class, 'updateSettings'])->name('settings.update');
            Route::post('clients/{id}/adjust', [CreditController::class, 'adjustCredits'])->whereNumber('id')->name('adjust');
            Route::get('clients/{id}/history', [CreditController::class, 'clientHistory'])->whereNumber('id')->name('history');
        });

        Route::prefix('help')->name('help.')->group(function () {
            Route::get('faqs', [HelpController::class, 'faqs'])->name('faqs');
            Route::post('faqs', [HelpController::class, 'storeFaq'])->name('faqs.store');
            Route::put('faqs/{id}', [HelpController::class, 'updateFaq'])->whereNumber('id')->name('faqs.update');
            Route::delete('faqs/{id}', [HelpController::class, 'deleteFaq'])->whereNumber('id')->name('faqs.destroy');
            Route::get('tickets', [HelpController::class, 'tickets'])->name('tickets');
            Route::get('tickets/{id}', [HelpController::class, 'showTicket'])->whereNumber('id')->name('tickets.show');
            Route::post('tickets/{id}/reply', [HelpController::class, 'replyTicket'])->whereNumber('id')->name('tickets.reply');
            Route::post('tickets/{id}/status', [HelpController::class, 'updateTicketStatus'])->whereNumber('id')->name('tickets.status');
            Route::post('tickets/{id}/assign', [HelpController::class, 'assignTicket'])->whereNumber('id')->name('tickets.assign');
            Route::delete('tickets/{id}', [HelpController::class, 'deleteTicket'])->whereNumber('id')->name('tickets.destroy');
        });

        Route::get('invoices/{id}/preview', [InvoiceController::class, 'preview'])->whereNumber('id')->name('invoices.preview');
        Route::get('invoices/{id}/download', [InvoiceController::class, 'download'])->whereNumber('id')->name('invoices.download');
        Route::delete('invoices/{id}', [InvoiceController::class, 'destroy'])->whereNumber('id')->name('invoices.destroy');
    });
});
