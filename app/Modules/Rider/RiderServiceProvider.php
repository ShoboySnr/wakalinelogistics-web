<?php

namespace App\Modules\Rider;

use Illuminate\Support\ServiceProvider;

class RiderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/Views', 'Rider');
    }

    public function register()
    {
        //
    }
}
