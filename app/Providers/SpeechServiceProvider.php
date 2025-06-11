<?php

namespace App\Providers;

use App\Services\SpeechService;
use Illuminate\Support\ServiceProvider;

class SpeechServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SpeechService::class, function ($app) {
            return new SpeechService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 