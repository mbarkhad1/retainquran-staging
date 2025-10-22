<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Payments\PaymentGatewayInterface;
use App\Payments\StripePaymentGateway;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return new StripePaymentGateway();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
