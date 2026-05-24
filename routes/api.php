<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ClientOrderController;
use App\Http\Controllers\Api\ClientCustomerController;
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

Route::post('jobs/apply', [JobApplicationController::class, 'submit']);

Route::prefix('wakalinelogistics/v1')->group(function () {
    Route::prefix('client-share')->group(function () {
        Route::get('{token}/orders', [ClientShareController::class, 'getOrders']);
        Route::get('{token}/info', [ClientShareController::class, 'getInfo']);
    });
    Route::post('orders/submit-public', [\App\Http\Controllers\Api\OrderController::class, 'submitOrder'])->middleware('client.api');
    
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/client-register', [AuthController::class, 'clientRegister']);
    Route::post('auth/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('auth/resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/verify-2fa', [AuthController::class, 'verify2FA']);
    Route::post('auth/resend-2fa', [AuthController::class, 'resend2FACode']);
    Route::post('auth/social-login', [AuthController::class, 'socialLogin']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/user', [AuthController::class, 'user']);
        
        // Client Orders
        Route::post('orders', [ClientOrderController::class, 'store']);
        Route::post('orders/subscription', [ClientOrderController::class, 'storeWithSubscription']);
        Route::post('orders/card/initialize', [ClientOrderController::class, 'initializeCardPayment']);
        // All aliases for card payment verification (covers all deployed frontend versions)
        Route::post('orders/confirm-payment', [ClientOrderController::class, 'verifyCardPayment']);
        Route::post('orders/card/verify', [ClientOrderController::class, 'verifyCardPayment']);
        Route::post('orders/card/verified', [ClientOrderController::class, 'verifyCardPayment']);
        Route::get('orders/my-orders', [ClientOrderController::class, 'myOrders']);
        Route::get('orders/today', [ClientOrderController::class, 'todayOrders']);
        Route::get('orders/bulk/template', [ClientOrderController::class, 'bulkTemplate']);
        Route::post('orders/bulk', [ClientOrderController::class, 'bulkUpload']);
        // All aliases for bulk card payment verification
        Route::post('orders/bulk/confirm-payment', [ClientOrderController::class, 'verifyBulkCardPayment']);
        Route::post('orders/bulk/card/verify', [ClientOrderController::class, 'verifyBulkCardPayment']);
        Route::get('orders/share-link', [ClientOrderController::class, 'getShareLink']);
        Route::get('orders/{id}', [ClientOrderController::class, 'show']);
        Route::post('orders/{id}/cancel', [ClientOrderController::class, 'cancel']);
        Route::post('orders/{id}/images', [ClientOrderController::class, 'uploadImages']);
        
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
        Route::get('settings/2fa', [ClientSettingsController::class, 'get2FASettings']);
        Route::post('settings/2fa/authenticator/setup', [ClientSettingsController::class, 'setupAuthenticator']);
        Route::post('settings/2fa/authenticator/confirm', [ClientSettingsController::class, 'confirmAuthenticator']);
        Route::post('settings/2fa/recovery-codes/regenerate', [ClientSettingsController::class, 'regenerateRecoveryCodes']);
        
        // Credits & Subscriptions Routes
        Route::prefix('credits')->group(function () {
            Route::get('/balance', [\App\Http\Controllers\Api\CreditController::class, 'getBalance']);
            Route::get('/plans', [\App\Http\Controllers\Api\CreditController::class, 'getPlans']);
            Route::get('/packages', [\App\Http\Controllers\Api\CreditController::class, 'getPackages']);
            Route::post('/plans/purchase', [\App\Http\Controllers\Api\CreditController::class, 'purchasePlan']);
            Route::post('/packages/purchase', [\App\Http\Controllers\Api\CreditController::class, 'purchasePackage']);
            Route::post('/custom/purchase', [\App\Http\Controllers\Api\CreditController::class, 'purchaseCustomAmount']);
            Route::post('/verify', [\App\Http\Controllers\Api\CreditController::class, 'verifyPayment']);
            Route::get('/transactions', [\App\Http\Controllers\Api\CreditController::class, 'getTransactions']);
            Route::get('/subscriptions', [\App\Http\Controllers\Api\CreditController::class, 'getSubscriptions']);
            Route::post('/calculate', [\App\Http\Controllers\Api\CreditController::class, 'calculateDeliveryCredits']);
            Route::get('/zones', [\App\Http\Controllers\Api\CreditController::class, 'getDeliveryZones']);
        });

        // Customers — settings route MUST be before {phone} wildcard
        Route::get('customers/settings',         [ClientCustomerController::class, 'getSettings']);
        Route::patch('customers/settings',        [ClientCustomerController::class, 'updateSettings']);
        Route::get('customers',                   [ClientCustomerController::class, 'index']);
        Route::get('customers/{phone}',           [ClientCustomerController::class, 'show'])->where('phone', '.+');
        Route::patch('customers/{phone}/meta',    [ClientCustomerController::class, 'updateMeta'])->where('phone', '.+');

        // Integrations
        Route::get('integrations',                [\App\Http\Controllers\Api\ClientIntegrationsController::class, 'index']);
        Route::get('integrations/{type}',         [\App\Http\Controllers\Api\ClientIntegrationsController::class, 'show']);
        Route::post('integrations/{type}',        [\App\Http\Controllers\Api\ClientIntegrationsController::class, 'store']);
        Route::post('integrations/{type}/toggle', [\App\Http\Controllers\Api\ClientIntegrationsController::class, 'toggle']);
        Route::delete('integrations/{type}',      [\App\Http\Controllers\Api\ClientIntegrationsController::class, 'destroy']);
        Route::get('integrations/{type}/logs',    [\App\Http\Controllers\Api\ClientIntegrationsController::class, 'logs']);

        // Feature Suggestions
        Route::get('suggestions',                 [\App\Http\Controllers\Api\FeatureSuggestionController::class, 'index']);
        Route::get('suggestions/my',              [\App\Http\Controllers\Api\FeatureSuggestionController::class, 'mySuggestions']);
        Route::get('suggestions/stats',           [\App\Http\Controllers\Api\FeatureSuggestionController::class, 'stats']);
        Route::post('suggestions',                [\App\Http\Controllers\Api\FeatureSuggestionController::class, 'store']);
        Route::post('suggestions/{id}/upvote',    [\App\Http\Controllers\Api\FeatureSuggestionController::class, 'upvote']);
        Route::delete('suggestions/{id}/upvote',  [\App\Http\Controllers\Api\FeatureSuggestionController::class, 'removeVote']);

        // Reports & Export
        Route::get('reports/branding',            [\App\Http\Controllers\Api\ReportsController::class, 'getBranding']);
        Route::put('reports/branding',            [\App\Http\Controllers\Api\ReportsController::class, 'updateBranding']);
        Route::get('reports/stats',               [\App\Http\Controllers\Api\ReportsController::class, 'getStats']);
        Route::post('reports/generate',           [\App\Http\Controllers\Api\ReportsController::class, 'generateReport']);
        Route::get('reports/history',             [\App\Http\Controllers\Api\ReportsController::class, 'exportHistory']);
        Route::get('reports/templates',           [\App\Http\Controllers\Api\ReportsController::class, 'getTemplates']);
        Route::post('reports/templates',          [\App\Http\Controllers\Api\ReportsController::class, 'createTemplate']);
        Route::put('reports/templates/{id}',      [\App\Http\Controllers\Api\ReportsController::class, 'updateTemplate']);
        Route::delete('reports/templates/{id}',   [\App\Http\Controllers\Api\ReportsController::class, 'deleteTemplate']);

        // Help Center
        Route::prefix('help')->group(function () {
            Route::get('/faqs', [\App\Http\Controllers\Api\HelpController::class, 'getFaqs']);
            Route::get('/tickets', [\App\Http\Controllers\Api\HelpController::class, 'getTickets']);
            Route::post('/tickets', [\App\Http\Controllers\Api\HelpController::class, 'createTicket']);
            Route::get('/tickets/{id}', [\App\Http\Controllers\Api\HelpController::class, 'getTicket']);
            Route::post('/tickets/{id}/messages', [\App\Http\Controllers\Api\HelpController::class, 'addMessage']);
            Route::patch('/tickets/{id}/close', [\App\Http\Controllers\Api\HelpController::class, 'closeTicket']);
        });
    });
    
    Route::get('credits/plans/public', [\App\Http\Controllers\Api\CreditController::class, 'getPublicPlans']);
    Route::get('credits/stats/public', [\App\Http\Controllers\Api\CreditController::class, 'getPublicStats']);
    Route::get('orders/track/{orderNumber}', [ClientOrderController::class, 'publicTrack']);
    Route::get('help/faqs', [\App\Http\Controllers\Api\HelpController::class, 'getFaqs']);

    Route::post('orders/submit', [OrderController::class, 'submitOrder'])->middleware('client.api');
    Route::get('orders/{orderNumber}/status', [OrderController::class, 'getOrderStatus'])->middleware('client.api');

    // Admin Routes (Protected by admin middleware)
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        // Feature Suggestions Management
        Route::get('suggestions', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'index']);
        Route::get('suggestions/statistics', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'statistics']);
        Route::get('suggestions/{id}', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'show']);
        Route::put('suggestions/{id}/status', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'updateStatus']);
        Route::post('suggestions/{id}/response', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'addResponse']);
        Route::post('suggestions/{id}/reward', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'awardReward']);
        Route::delete('suggestions/{id}', [\App\Modules\Admin\Controllers\FeatureSuggestionController::class, 'destroy']);
    });
});
