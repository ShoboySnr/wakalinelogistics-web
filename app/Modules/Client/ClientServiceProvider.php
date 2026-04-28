<?php

namespace App\Modules\Client;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ClientServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register views
        View::addNamespace('Client', base_path('app/Modules/Client/Views'));
    }
}
