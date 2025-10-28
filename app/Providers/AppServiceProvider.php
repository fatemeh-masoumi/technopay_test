<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Payment\WalletPaymentStrategy;
use App\Services\Payment\PaymentStrategyInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->bind(PaymentStrategyInterface::class, WalletPaymentStrategy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
