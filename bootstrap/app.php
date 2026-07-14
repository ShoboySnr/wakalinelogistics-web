<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('app/Modules/DeliveryCalculator/Routes/web.php'));
            
            Route::middleware('web')
                ->group(base_path('app/Modules/Admin/Routes/web.php'));
            
            Route::middleware('web')
                ->group(base_path('app/Modules/Client/Routes/web.php'));
            
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('app/Modules/DeliveryCalculator/Routes/api.php'));
            
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('app/Modules/PublicApi/Routes/api.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('app/Modules/Admin/Routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.token' => \App\Http\Middleware\ValidateApiToken::class,
            'client.api' => \App\Http\Middleware\ValidateClientApiKey::class,
            'admin' => \App\Modules\Admin\Middleware\AdminMiddleware::class,
            'client.auth' => \App\Modules\Client\Middleware\ClientAuth::class,
        ]);
        
        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Redirect unauthenticated users to appropriate login pages
        $middleware->redirectGuestsTo(function ($request) {
            // For API requests, don't redirect - let Sanctum handle it
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            
            if ($request->is('client') || $request->is('client/*')) {
                return route('client.login');
            }
            return route('admin.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
