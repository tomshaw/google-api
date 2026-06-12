<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Enums;

enum ApprovalPrompt: string
{
    case Auto = 'auto';
    case Force = 'force';
}
