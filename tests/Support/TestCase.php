<?php

namespace TomShaw\GoogleApi\Tests\Support;

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

    protected function getPackageProviders($app)
    {
        return [
            GoogleApiServiceProvider::class,
        ];
    }
}
