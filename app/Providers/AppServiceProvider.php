<?php

namespace App\Providers;

use App\Services\Shoptok\FixtureShoptokHtmlSource;
use App\Services\Shoptok\ShoptokHtmlSource;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ShoptokHtmlSource::class, function () {
            return new FixtureShoptokHtmlSource(
                basePath: resource_path('fixtures/shoptok/')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
