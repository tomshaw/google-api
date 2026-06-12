<?php

declare(strict_types=1);

namespace TomShaw\GoogleApi\Storage;

class SessionStorageAdapter implements StorageAdapterInterface
{
    public const SESSION_KEY = 'google_api_token';

    /**
     * @param  array<string, mixed>  $accessToken
     */
    public function set(array $accessToken): self
    {
        session([self::SESSION_KEY => $accessToken]);

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        return session()->get(self::SESSION_KEY);
    }

    public function delete(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
