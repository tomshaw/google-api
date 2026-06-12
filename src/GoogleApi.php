<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin GoogleApiManager
 */
class GoogleApi extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return GoogleApiManager::class;
    }
}
