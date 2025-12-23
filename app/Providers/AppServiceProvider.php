<?php

namespace App\Providers;

use App\Events\SubOrderCreated;
use App\Listeners\NotifyVendorOnSubOrderCreated;
use App\Services\Discount\DiscountEngine;
use App\Services\OrderProcessor;
use App\Services\PriceEngine;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PriceEngine::class);
        $this->app->bind(OrderProcessor::class);
        $this->app->bind(DiscountEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
