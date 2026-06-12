<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Providers;

use Illuminate\Support\ServiceProvider;
use TomShaw\GoogleApi\GoogleClient;
use TomShaw\GoogleApi\Storage\StorageAdapterInterface;

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

        $this->app->bind(StorageAdapterInterface::class, fn () => app(config('google-api.token_storage_adapter')));

        $this->app->scoped(GoogleClient::class, fn () => GoogleClient::make());
    }
}
