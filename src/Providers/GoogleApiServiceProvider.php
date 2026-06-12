<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Providers;

use Illuminate\Support\ServiceProvider;
use TomShaw\GoogleApi\Exceptions\GoogleClientException;
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

        $this->app->bind(StorageAdapterInterface::class, function (): StorageAdapterInterface {
            $adapter = config('google-api.token_storage_adapter');

            if (! is_string($adapter)) {
                throw new GoogleClientException('The [google-api.token_storage_adapter] config value must be a class implementing StorageAdapterInterface.');
            }

            $instance = app($adapter);

            if (! $instance instanceof StorageAdapterInterface) {
                throw new GoogleClientException('The [google-api.token_storage_adapter] config value must be a class implementing StorageAdapterInterface.');
            }

            return $instance;
        });

        $this->app->scoped(GoogleClient::class, fn () => GoogleClient::make());
    }
}
