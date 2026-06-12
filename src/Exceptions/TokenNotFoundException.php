<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Exceptions;

use RuntimeException;

final class TokenNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'No access token found. Redirect the user to the authorization URL.',
        public readonly ?string $authUrl = null,
    ) {
        parent::__construct($message);
    }
}
