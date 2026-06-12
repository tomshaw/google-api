<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Enums;

enum AccessType: string
{
    case Online = 'online';
    case Offline = 'offline';
}
