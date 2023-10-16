<?php

namespace TomShaw\GoogleApi;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \TomShaw\GoogleApi\GoogleApiManager
 */
class GoogleApi extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return GoogleApiManager::class;
    }
}
