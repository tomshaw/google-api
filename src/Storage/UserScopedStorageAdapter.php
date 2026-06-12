<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Storage;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserScopedStorageAdapter extends StorageAdapterInterface
{
    /**
     * Scope token storage to the given user instead of the authenticated user.
     */
    public function forUser(Authenticatable|int|string|null $user): static;
}
