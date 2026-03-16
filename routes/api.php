<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RouteShareApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route Share API (Public - no auth required)
Route::prefix('route-share')->group(function () {
    Route::get('{token}/data', [RouteShareApiController::class, 'getRouteData']);
    Route::post('{token}/location', [RouteShareApiController::class, 'updateRiderLocation']);
    Route::post('{token}/orders/{orderId}/status', [RouteShareApiController::class, 'updateOrderStatus']);
    Route::post('{token}/validate-code', [RouteShareApiController::class, 'validateDailyCode']);
});

// Public order submission endpoint (no auth required for website forms)
Route::post('orders/submit-public', [\App\Http\Controllers\Api\OrderController::class, 'submitOrder']);

// Metter API Routes
Route::prefix('wakalinelogistics/v1')->group(function () {
    // Public authentication routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/social-login', [AuthController::class, 'socialLogin']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/user', [AuthController::class, 'user']);
    });
    
    // Order submission endpoint (with API token)
    Route::post('orders/submit', [OrderController::class, 'submitOrder'])->middleware('api.token');
});
