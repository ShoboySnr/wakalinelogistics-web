<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Client\Controllers\ClientAuthController;
use App\Modules\Client\Controllers\ClientDashboardController;

// Test route to verify routes are loading
Route::get('/client-test', function () {
    return 'Client routes are working!';
});

// Test route to verify controller is accessible
Route::get('/client-controller-test', function () {
    try {
        $controller = new \App\Modules\Client\Controllers\ClientDashboardController();
        return 'ClientDashboardController exists and can be instantiated!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// Test route without middleware
Route::get('/client-dashboard-test', [\App\Modules\Client\Controllers\ClientDashboardController::class, 'index']);

// Test route to check logged in client status
Route::get('/client-status-check', function () {
    if (!\Illuminate\Support\Facades\Auth::guard('client')->check()) {
        return 'Not logged in. Please login first at /client';
    }
    
    $client = \Illuminate\Support\Facades\Auth::guard('client')->user();
    
    return [
        'logged_in' => true,
        'client_id' => $client->id,
        'client_name' => $client->name,
        'client_email' => $client->email,
        'is_active' => $client->is_active ? 'Yes' : 'No',
        'dashboard_enabled' => $client->dashboard_enabled ? 'Yes' : 'No',
        'can_access_dashboard' => ($client->is_active && $client->dashboard_enabled) ? 'Yes' : 'No',
        'reason_blocked' => (!$client->is_active ? 'Account is not active' : (!$client->dashboard_enabled ? 'Dashboard access is disabled' : 'None')),
    ];
});

// Test route to check if dashboard route works with middleware
Route::get('/client-dashboard-with-middleware', function () {
    try {
        $controller = app(\App\Modules\Client\Controllers\ClientDashboardController::class);
        return $controller->index();
    } catch (\Exception $e) {
        return [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
    }
})->middleware(['client.auth']);

// Client Routes
Route::prefix('client')->group(function () {
    // Authentication Routes
    Route::get('/', [ClientAuthController::class, 'showLoginForm'])->name('client.login');
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
