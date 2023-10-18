<?php

namespace TomShaw\GoogleApi\Providers;

use Google\Client;
use Illuminate\Support\ServiceProvider;
use TomShaw\GoogleApi\Contracts\GoogleClientInterface;
use TomShaw\GoogleApi\GoogleClient;

class GoogleApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../resources/database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config/config.php' => config_path('google-api.php')], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'google-api');

        $this->app->bind(GoogleClientInterface::class, fn () => new GoogleClient(new Client()));
    }
}
