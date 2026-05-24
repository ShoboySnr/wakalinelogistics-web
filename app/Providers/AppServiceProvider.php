<?php

namespace App\Providers;

use App\Mail\Transport\SendgridTransport;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
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
        Mail::extend('sendgrid', function (array $config = []) {
            return new SendgridTransport($config['key'], new Client());
        });

        $this->loadViewsFrom(
            app_path('Modules/DeliveryCalculator/Views'),
            'delivery-calculator'
        );

        $this->loadViewsFrom(
            app_path('Modules/LandingPage/Views'),
            'landing-page'
        );

        // Admin module views
        $this->loadViewsFrom(
            app_path('Modules/Admin/Views'),
            'Admin'
        );
    }
}
