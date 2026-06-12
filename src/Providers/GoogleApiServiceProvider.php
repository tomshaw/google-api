<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Providers;

use Google\Client;
use Illuminate\Support\ServiceProvider;
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

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'google-api');

        $this->app->scoped(GoogleClient::class, fn () => new GoogleClient(new Client));
    }
}
