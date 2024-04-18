<?php

namespace TomShaw\GoogleApi\Models;

use Illuminate\Support\Collection;

class StorageCollection extends Collection
{
    public int $id;

    public int $user_id;

    public string $access_token;

    public string $refresh_token;

    public int $expires_in;

    public string $scope;

    public string $token_type;

    public int $created;
}
