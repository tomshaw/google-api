<?php

namespace TomShaw\GoogleApi\Providers;

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
            $this->publishes([__DIR__.'/../../config/scopes.php' => config_path('google-api-scopes.php')], 'config');
            $this->publishes([__DIR__.'/../../config/service.php' => config_path('google-api-service.php')], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'google-api');
        $this->mergeConfigFrom(__DIR__.'/../../config/scopes.php', 'google-api.scopes');
        $this->mergeConfigFrom(__DIR__.'/../../config/service.php', 'google-api.service');

        $this->app->bind(GoogleClientInterface::class, GoogleClient::class);
    }
}
