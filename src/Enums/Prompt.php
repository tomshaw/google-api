<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Enums;

enum Prompt: string
{
    case None = 'none';
    case Consent = 'consent';
    case SelectAccount = 'select_account';
}
