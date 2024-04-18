<?php

namespace TomShaw\GoogleApi\Storage;

class SessionStorageAdapter implements StorageAdapterInterface
{
    public const SESSION_KEY = 'google_api_token';

    public function set(array $accessToken): self
    {
        session([self::SESSION_KEY => $accessToken]);

        return $this;
    }

    public function get(): ?array
    {
        return session()->get(self::SESSION_KEY);
    }

    public function delete(): null
    {
        session()->forget(self::SESSION_KEY);

        return null;
    }
}
