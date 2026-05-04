<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ClientOrderController;
use App\Http\Controllers\Api\ClientSettingsController;
use App\Http\Controllers\Api\RouteShareApiController;
use App\Http\Controllers\Api\JobApplicationController;
use App\Http\Controllers\ClientShareController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('route-share')->group(function () {
    Route::get('{riderId}/data', [RouteShareApiController::class, 'getRouteData']);
    Route::get('{riderId}/grouped-orders', [RouteShareApiController::class, 'getGroupedOrdersHtml']);
    Route::post('{riderId}/location', [RouteShareApiController::class, 'updateRiderLocation']);
    Route::post('{riderId}/orders/{orderId}/status', [RouteShareApiController::class, 'updateOrderStatus']);
    Route::post('{riderId}/validate-code', [RouteShareApiController::class, 'validateDailyCode']);
});

Route::prefix('client-share')->group(function () {
    Route::get('{token}/orders', [ClientShareController::class, 'getOrders']);
});

Route::post('jobs/apply', [JobApplicationController::class, 'submit']);

Route::prefix('wakalinelogistics/v1')->group(function () {
    Route::post('orders/submit-public', [\App\Http\Controllers\Api\OrderController::class, 'submitOrder'])->middleware('client.api');
    
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/social-login', [AuthController::class, 'socialLogin']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/user', [AuthController::class, 'user']);
        
        // Client Orders
        Route::post('orders', [ClientOrderController::class, 'store']);
        Route::get('orders/my-orders', [ClientOrderController::class, 'myOrders']);
        Route::get('orders/today', [ClientOrderController::class, 'todayOrders']);
        Route::get('orders/{id}', [ClientOrderController::class, 'show']);
        
        // Client Settings
        Route::put('settings/profile', [ClientSettingsController::class, 'updateProfile']);
        Route::put('settings/pickup-details', [ClientSettingsController::class, 'updatePickupDetails']);
        Route::get('settings/pickup-details', [ClientSettingsController::class, 'getPickupDetails']);
        Route::put('settings/password', [ClientSettingsController::class, 'changePassword']);
        Route::put('settings/theme', [ClientSettingsController::class, 'updateThemePreference']);
        Route::put('settings/preferences', [ClientSettingsController::class, 'updatePreferences']);
        Route::put('settings/notifications', [ClientSettingsController::class, 'updateNotificationPreferences']);
        Route::post('settings/api-access', [ClientSettingsController::class, 'toggleApiAccess']);
        Route::get('settings/api-access', [ClientSettingsController::class, 'getApiAccess']);
        Route::post('settings/api-key/regenerate', [ClientSettingsController::class, 'regenerateApiKey']);
        Route::post('settings/2fa', [ClientSettingsController::class, 'toggle2FA']);
    });
    
    Route::post('orders/submit', [OrderController::class, 'submitOrder'])->middleware('client.api');
    Route::get('orders/{orderNumber}/status', [OrderController::class, 'getOrderStatus'])->middleware('client.api');
});
