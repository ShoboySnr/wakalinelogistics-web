<?php

use App\Modules\Admin\Controllers\WhatsAppBotController;
use Illuminate\Support\Facades\Route;

Route::prefix('bot/v1')->name('api.bot.')->middleware('api.token:bot')->group(function () {

    // Orders
    Route::get('/orders', [WhatsAppBotController::class, 'listOrders'])->name('orders.index');
    Route::post('/orders/quote', [WhatsAppBotController::class, 'quote'])->name('orders.quote');
    Route::get('/orders/remit-summary', [WhatsAppBotController::class, 'remitSummary'])->name('orders.remit-summary');
    Route::post('/orders/bulk-remit', [WhatsAppBotController::class, 'bulkRemit'])->name('orders.bulk-remit');
    Route::post('/orders', [WhatsAppBotController::class, 'createOrder'])->name('orders.create');
    Route::post('/orders/{order_number}/clone', [WhatsAppBotController::class, 'cloneOrder'])->name('orders.clone');
    Route::get('/orders/{order_number}', [WhatsAppBotController::class, 'getOrder'])->name('orders.show');
    Route::patch('/orders/{order_number}', [WhatsAppBotController::class, 'updateOrder'])->name('orders.update');
    Route::patch('/orders/{order_number}/status', [WhatsAppBotController::class, 'updateStatus'])->name('orders.update-status');
    Route::patch('/orders/{order_number}/rider', [WhatsAppBotController::class, 'assignRider'])->name('orders.assign-rider');
    Route::patch('/orders/{order_number}/remit', [WhatsAppBotController::class, 'remitOrder'])->name('orders.remit');
    Route::delete('/orders/{order_number}', [WhatsAppBotController::class, 'deleteOrder'])->name('orders.delete');

    // Riders
    Route::get('/riders', [WhatsAppBotController::class, 'listRiders'])->name('riders.index');
    Route::get('/riders/{id}/report', [WhatsAppBotController::class, 'riderReport'])->name('riders.report');
    Route::patch('/riders/{id}/status', [WhatsAppBotController::class, 'setRiderStatus'])->name('riders.set-status');

    // Rider Applications
    Route::post('/rider-applications', [WhatsAppBotController::class, 'submitRiderApplication'])->name('rider-applications.store');

    // Clients
    Route::get('/clients', [WhatsAppBotController::class, 'listClients'])->name('clients.index');
    Route::post('/clients', [WhatsAppBotController::class, 'createClient'])->name('clients.create');
    Route::get('/clients/lookup', [WhatsAppBotController::class, 'lookupClient'])->name('clients.lookup');
    Route::get('/clients/{ref}/orders', [WhatsAppBotController::class, 'clientOrders'])->name('clients.orders');
    Route::get('/clients/{ref}', [WhatsAppBotController::class, 'getClient'])->name('clients.show');
    Route::patch('/clients/{ref}', [WhatsAppBotController::class, 'updateClient'])->name('clients.update');
    Route::delete('/clients/{ref}', [WhatsAppBotController::class, 'deactivateClient'])->name('clients.deactivate');

    // Users
    Route::get('/users/lookup', [WhatsAppBotController::class, 'lookupUser'])->name('users.lookup');

    // Stats & Expenses
    Route::get('/stats', [WhatsAppBotController::class, 'getStats'])->name('stats');
    Route::get('/stats/top-clients', [WhatsAppBotController::class, 'topClients'])->name('stats.top-clients');
    Route::get('/stats/rider-earnings', [WhatsAppBotController::class, 'riderEarnings'])->name('stats.rider-earnings');
    Route::get('/expenses', [WhatsAppBotController::class, 'listExpenses'])->name('expenses.index');
    Route::get('/expenses/breakdown', [WhatsAppBotController::class, 'expenseBreakdown'])->name('expenses.breakdown');
    Route::post('/expenses', [WhatsAppBotController::class, 'addExpense'])->name('expenses.store');
    Route::delete('/expenses/{id}', [WhatsAppBotController::class, 'deleteExpense'])->name('expenses.delete');

    // Universal Search
    Route::get('/search', [WhatsAppBotController::class, 'universalSearch'])->name('search');

});
