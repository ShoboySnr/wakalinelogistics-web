<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(
            app_path('Modules/DeliveryCalculator/Views'),
            'delivery-calculator'
        );

        $this->loadViewsFrom(
            app_path('Modules/LandingPage/Views'),
            'landing-page'
        );
    }
}
