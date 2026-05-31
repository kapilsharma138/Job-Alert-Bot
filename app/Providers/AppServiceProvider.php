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
        $this->app->bind(\App\Console\Commands\FetchJobAlerts::class, function ($app) {
            return new \App\Console\Commands\FetchJobAlerts(
                $app->make(\App\Services\JobFetcher::class),
                $app->make(\App\Services\JobScorer::class),
                $app->make(\App\Services\JobDeduplicator::class),
                $app->make(\App\Services\TelegramNotifier::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
