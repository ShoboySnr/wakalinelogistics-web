<?php

namespace App\Modules\Metter;

use Illuminate\Support\ServiceProvider;

class MetterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/Views', 'Metter');
    }

    public function register()
    {
        //
    }
}
