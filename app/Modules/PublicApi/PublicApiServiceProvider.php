<?php

namespace App\Modules\PublicApi;

use Illuminate\Support\ServiceProvider;

class PublicApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/api.php');
    }

    public function register()
    {
        // Register bindings if needed
    }
}
