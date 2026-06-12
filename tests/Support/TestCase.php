<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Tests\Support;

use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as Orchestra;
use TomShaw\GoogleApi\Providers\GoogleApiServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(Str::random(32)));
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            GoogleApiServiceProvider::class,
        ];
    }
}
