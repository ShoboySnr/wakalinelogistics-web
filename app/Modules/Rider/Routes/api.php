<?php

use App\Modules\Rider\Controllers\Api\AuthController;
use App\Modules\Rider\Controllers\Api\OrdersController;
use App\Modules\Rider\Controllers\Api\PresenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('rider/v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/otp/request', [AuthController::class, 'requestOtp']);
    Route::post('auth/otp/verify', [AuthController::class, 'verifyOtp']);

    Route::middleware(['auth:rider'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/pin', [AuthController::class, 'setPin']);
        Route::post('auth/devices', [AuthController::class, 'registerDevice']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('orders', [OrdersController::class, 'index']);
        Route::get('orders/{id}', [OrdersController::class, 'show']);
        Route::post('orders/{id}/status', [OrdersController::class, 'updateStatus']);
        Route::post('orders/{id}/fail', [OrdersController::class, 'fail']);
        Route::post('orders/{id}/proof', [OrdersController::class, 'uploadProof']);

        Route::post('availability', [PresenceController::class, 'setAvailability']);
        Route::post('location', [PresenceController::class, 'pushLocation']);
        Route::get('profile', [PresenceController::class, 'showProfile']);
        Route::put('profile', [PresenceController::class, 'updateProfile']);
    });
});
