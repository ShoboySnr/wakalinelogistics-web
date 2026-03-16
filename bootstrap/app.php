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
                ->group(base_path('app/Modules/LandingPage/Routes/web.php'));

            Route::middleware('web')
                ->group(base_path('app/Modules/DeliveryCalculator/Routes/web.php'));
            
            Route::middleware('web')
                ->group(base_path('app/Modules/Admin/Routes/web.php'));
            
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('app/Modules/DeliveryCalculator/Routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.token' => \App\Http\Middleware\ValidateApiToken::class,
            'admin' => \App\Modules\Admin\Middleware\AdminMiddleware::class,
        ]);
        
        // Redirect unauthenticated users to admin login for admin routes
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('super-admin') || $request->is('super-admin/*')) {
                return route('admin.login');
            }
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
