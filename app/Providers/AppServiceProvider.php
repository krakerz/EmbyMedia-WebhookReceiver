<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TvdbService;
use App\Services\ImdbService;
use App\Services\EmbyService;
use App\Services\ImageFetchingService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TvdbService::class);
        $this->app->singleton(ImdbService::class);
        $this->app->singleton(EmbyService::class);
        $this->app->singleton(ImageFetchingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
