<?php

namespace App\Providers;

use App\Services\FixturePredictService;
use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

class PredictionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(GeminiService::class, function ($app) {
            return new GeminiService();
        });

        $this->app->singleton(FixturePredictService::class, function ($app) {
            return new FixturePredictService(
                $app->make('App\Services\FixtureService'),
                $app->make(GeminiService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
